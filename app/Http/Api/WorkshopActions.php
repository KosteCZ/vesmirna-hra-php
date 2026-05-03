<?php

function handleWorkshopAction(string $action, int $userId, PDO $db): bool
{
    if ($action === 'research_rocket_workshop') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_advanced_lab']) {
            echo json_encode(['error' => 'Musíš mít Pokročilou laboratoř!']);
            return true;
        }
        if ($planet['research_rocket_workshop']) {
            echo json_encode(['error' => 'Raketová dílna je již postavena!']);
            return true;
        }

        if ($planet['res_tubes'] >= ROCKET_WORKSHOP_RESEARCH_COST) {
            researchRocketWorkshop($db, $userId, ROCKET_WORKSHOP_RESEARCH_COST, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek zkumavek (15000)!']);
        }

        return true;
    }

    if ($action === 'upgrade_rocket_workshop') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_rocket_workshop']) {
            echo json_encode(['error' => 'Raketová dílna není postavena!']);
            return true;
        }
        if (($planet['rocket_workshop_level'] ?? 1) >= 2) {
            echo json_encode(['error' => 'Raketová dílna je již na maximální úrovni!']);
            return true;
        }

        if ($planet['iron_amount'] >= ROCKET_WORKSHOP_UPGRADE_COST) {
            upgradeRocketWorkshop($db, $userId, ROCKET_WORKSHOP_UPGRADE_COST, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek železa (1 000 000 Fe)!']);
        }

        return true;
    }

    if ($action === 'start_rocket_workshop_production') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_rocket_workshop']) {
            echo json_encode(['error' => 'Raketová dílna není postavena!']);
            return true;
        }

        $mode = isset($_POST['mode']) ? (int) $_POST['mode'] : 1;
        $statusCol = ($mode === 2) ? 'rocket_workshop_2_status' : 'rocket_workshop_status';
        $startCol = ($mode === 2) ? 'rocket_workshop_2_started_at' : 'rocket_workshop_started_at';
        $readyCol = ($mode === 2) ? 'rocket_workshop_2_ready_at' : 'rocket_workshop_ready_at';

        if ($mode === 2 && ($planet['rocket_workshop_level'] ?? 1) < 2) {
            echo json_encode(['error' => 'Musíš vylepšit dílnu pro tento typ výroby!']);
            return true;
        }

        $rocketParts = $planet['rocket_parts'] ?? getDefaultRocketPartsInventory();
        $availableParts = array_filter($rocketParts, static fn ($count) => $count < 10);
        if (count($availableParts) === 0) {
            echo json_encode(['error' => 'Už máš vyrobeno vše 10x, další výroba není možná.']);
            return true;
        }
        if (($planet[$statusCol] ?? 'idle') === 'producing') {
            echo json_encode(['error' => 'V tomto slotu výroba už probíhá!']);
            return true;
        }
        if (($planet[$statusCol] ?? 'idle') === 'ready') {
            echo json_encode(['error' => 'Nejdřív si vyzvedni hotový výtvor v tomto slotu.']);
            return true;
        }

        $cost = ($mode === 2) ? ROCKET_WORKSHOP_PRODUCTION_COST_2 : ROCKET_WORKSHOP_PRODUCTION_COST;
        $duration = ($mode === 2) ? ROCKET_WORKSHOP_PRODUCTION_DURATION_2 : ROCKET_WORKSHOP_PRODUCTION_DURATION;
        if ($planet['res_tubes'] < $cost) {
            echo json_encode(['error' => "Nedostatek zkumavek ({$cost})!"]);
            return true;
        }

        $startedAt = new DateTime('now', new DateTimeZone('UTC'));
        $readyAt = clone $startedAt;
        $readyAt->modify('+' . $duration . ' seconds');

        startRocketWorkshopProduction(
            $db,
            $userId,
            $cost,
            $statusCol,
            $startCol,
            $readyCol,
            $startedAt->format('Y-m-d H:i:s'),
            $readyAt->format('Y-m-d H:i:s'),
            date('Y-m-d H:i:s')
        );
        echo json_encode(['success' => true]);
        return true;
    }

    if ($action === 'collect_rocket_workshop_product') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_rocket_workshop']) {
            echo json_encode(['error' => 'Raketová dílna není postavena!']);
            return true;
        }

        $slot = isset($_POST['slot']) ? (int) $_POST['slot'] : 1;
        $statusCol = ($slot === 2) ? 'rocket_workshop_2_status' : 'rocket_workshop_status';
        $startCol = ($slot === 2) ? 'rocket_workshop_2_started_at' : 'rocket_workshop_started_at';
        $readyCol = ($slot === 2) ? 'rocket_workshop_2_ready_at' : 'rocket_workshop_ready_at';

        if (($planet[$statusCol] ?? 'idle') !== 'ready') {
            echo json_encode(['error' => 'V tomto slotu zatím není nic k vyzvednutí.']);
            return true;
        }

        $rocketParts = $planet['rocket_parts'] ?? getDefaultRocketPartsInventory();
        $selectedParts = [];
        $partsToGrant = ($slot === 2) ? 2 : 1;
        $partDefinitions = getRocketPartDefinitions();

        for ($i = 0; $i < $partsToGrant; $i++) {
            $availableKeys = [];
            foreach ($rocketParts as $partKey => $partCount) {
                if ($partCount < 10) {
                    $availableKeys[] = $partKey;
                }
            }

            if (count($availableKeys) > 0) {
                $selectedKey = $availableKeys[random_int(0, count($availableKeys) - 1)];
                $rocketParts[$selectedKey] = min(10, ($rocketParts[$selectedKey] ?? 0) + 1);
                $selectedParts[] = $partDefinitions[$selectedKey] ?? $selectedKey;
            }
        }

        if (count($selectedParts) === 0) {
            resetRocketWorkshopSlotByPlanetId($db, (int) $planet['id'], $statusCol, $startCol, $readyCol, date('Y-m-d H:i:s'));
            echo json_encode(['error' => 'Už máš vyrobeno vše 10x, další výroba není možná.']);
            return true;
        }

        collectRocketWorkshopProduct($db, $userId, json_encode($rocketParts), $statusCol, $startCol, $readyCol, date('Y-m-d H:i:s'));

        echo json_encode([
            'success' => true,
            'parts' => $selectedParts,
            'part_label' => implode(' a ', $selectedParts),
        ]);
        return true;
    }

    if ($action === 'buy_rocket_workshop_part') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_rocket_workshop']) {
            echo json_encode(['error' => 'RaketovĂˇ dĂ­lna nenĂ­ postavena!']);
            return true;
        }
        if (($planet['game_state'] ?? '') !== 'SAND_STORM_COMING_2') {
            echo json_encode(['error' => 'NouzovĂ˝ nĂˇkup dĂ­lĹŻ zatĂ­m nenĂ­ dostupnĂ˝!']);
            return true;
        }
        if ($planet['crystal_amount'] < ROCKET_WORKSHOP_CRYSTAL_PART_COST) {
            echo json_encode(['error' => 'Nedostatek krystalĹŻ (50 000)!']);
            return true;
        }

        $rocketParts = $planet['rocket_parts'] ?? getDefaultRocketPartsInventory();
        $availableKeys = [];
        foreach ($rocketParts as $partKey => $partCount) {
            if ($partCount < 10) {
                $availableKeys[] = $partKey;
            }
        }

        if (count($availableKeys) === 0) {
            echo json_encode(['error' => 'UĹľ mĂˇĹˇ vyrobeno vĹˇe 10x, dalĹˇĂ­ nĂˇkup nenĂ­ moĹľnĂ˝.']);
            return true;
        }

        $selectedKey = $availableKeys[random_int(0, count($availableKeys) - 1)];
        $rocketParts[$selectedKey] = min(10, ($rocketParts[$selectedKey] ?? 0) + 1);
        $partDefinitions = getRocketPartDefinitions();
        $selectedPart = $partDefinitions[$selectedKey] ?? $selectedKey;

        buyRocketWorkshopPart($db, $userId, ROCKET_WORKSHOP_CRYSTAL_PART_COST, json_encode($rocketParts), date('Y-m-d H:i:s'));

        echo json_encode([
            'success' => true,
            'parts' => [$selectedPart],
            'part_label' => $selectedPart,
        ]);
        return true;
    }

    if ($action === 'launch_rocket') {
        $planet = getPlanetData($userId, $db);
        if (($planet['game_state'] ?? '') !== 'SAND_STORM_COMING_2') {
            echo json_encode(['error' => 'Raketa zatim nemuze odstartovat.']);
            return true;
        }
        if (!isInterstellarGateActive($db)) {
            echo json_encode(['error' => 'Mezihvezdna brana jeste neni aktivni.']);
            return true;
        }

        $rocketParts = $planet['rocket_parts'] ?? getDefaultRocketPartsInventory();
        foreach ($rocketParts as $count) {
            if ($count < 10) {
                echo json_encode(['error' => 'Raketa jeste nema vsechny soucastky.']);
                return true;
            }
        }

        updateGameState($db, 'WIN');
        echo json_encode(['success' => true]);
        return true;
    }

    return false;
}
