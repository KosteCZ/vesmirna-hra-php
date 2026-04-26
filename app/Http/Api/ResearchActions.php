<?php

function handleResearchAction(string $action, int $userId, PDO $db): bool
{
    if ($action === 'research_secret_crystal_mine') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_alien_slot_3']) {
            echo json_encode(['error' => 'MusÄ‚Â­ÄąË‡ mÄ‚Â­t nejdÄąâ„˘Ä‚Â­ve vyzkoumanÄ‚Ëť 3. slot pro alien doly!']);
            return true;
        }
        if ($planet['research_secret_crystal_mine']) {
            echo json_encode(['error' => 'VÄ‚Ëťzkum jiÄąÄľ mÄ‚Ë‡ÄąË‡!']);
            return true;
        }

        if ($planet['res_tubes'] >= SECRET_MINE_RESEARCH_COST_TUBES) {
            researchSecretCrystalMine($db, $userId, SECRET_MINE_RESEARCH_COST_TUBES, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek zkumavek pro vÄ‚Ëťzkum!']);
        }

        return true;
    }

    if ($action === 'research_alien_slot_3') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_rocket_workshop']) {
            echo json_encode(['error' => 'MusÄ‚Â­ÄąË‡ mÄ‚Â­t hotovou Raketovou dÄ‚Â­lnu!']);
            return true;
        }
        if ($planet['research_alien_slot_3']) {
            echo json_encode(['error' => 'VÄ‚Ëťzkum jiÄąÄľ mÄ‚Ë‡ÄąË‡!']);
            return true;
        }

        $minesAt50 = 0;
        foreach (ALLOWED_COLORS as $color) {
            if (($planet['alien_resources'][$color]['lvl'] ?? 0) >= 50) {
                $minesAt50++;
            }
        }

        if ($minesAt50 < 2) {
            echo json_encode(['error' => 'MusÄ‚Â­ÄąË‡ mÄ‚Â­t alespoÄąÂ 2 pÄąâ„˘edeÄąË‡lÄ‚Â© doly na Ä‚Ĺźrovni 50!']);
            return true;
        }

        if ($planet['iron_amount'] >= ALIEN_SLOT_3_IRON_COST &&
            $planet['res_copper'] >= ALIEN_SLOT_3_COPPER_COST &&
            $planet['res_tubes'] >= ALIEN_SLOT_3_TUBES_COST) {
            researchAlienSlot3($db, $userId, ALIEN_SLOT_3_IRON_COST, ALIEN_SLOT_3_COPPER_COST, ALIEN_SLOT_3_TUBES_COST, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek surovin pro vÄ‚Ëťzkum!']);
        }

        return true;
    }

    if ($action === 'research_color') {
        $color = $_POST['color'] ?? '';
        if (!in_array($color, ALLOWED_COLORS, true)) {
            echo json_encode(['error' => 'Neplatna barva!']);
            return true;
        }

        $planet = getPlanetData($userId, $db);
        $researched = $planet['researched_colors'] ?? [];
        $maxSlots = $planet['research_alien_slot_3'] ? 3 : 2;

        if (count($researched) >= $maxSlots) {
            echo json_encode(['error' => "JiÄąÄľ mÄ‚Ë‡ÄąË‡ vyzkoumÄ‚Ë‡no maximum barev ({$maxSlots})!"]);
            return true;
        }
        if (in_array($color, $researched, true)) {
            echo json_encode(['error' => 'Tato barva je jiÄąÄľ vyzkoumÄ‚Ë‡na!']);
            return true;
        }

        $count = count($researched);
        $cost = ($count === 0) ? 100 : (($count === 1) ? 2000 : 10000);
        if ($planet['crystal_amount'] >= $cost) {
            $researched[] = $color;
            $newList = implode(',', $researched);
            researchColor($db, $userId, $cost, $newList, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek krystalÄąĹ»!']);
        }

        return true;
    }

    if ($action === 'research_copper') {
        $planet = getPlanetData($userId, $db);
        if ($planet['research_copper']) {
            echo json_encode(['error' => 'MĂ„â€şĂ„Ĺą je jiÄąÄľ vyzkoumÄ‚Ë‡na!']);
            return true;
        }

        $hasEnoughMaterial = false;
        foreach ($planet['alien_resources'] as $res) {
            if ($res['amount'] >= 2000) {
                $hasEnoughMaterial = true;
                break;
            }
        }

        if (!$hasEnoughMaterial) {
            echo json_encode(['error' => 'PotÄąâ„˘ebujeÄąË‡ alespoÄąÂ 2000 jednoho druhu barevnÄ‚Â©ho materiÄ‚Ë‡lu!']);
            return true;
        }

        $ironCost = 50000;
        $crystalCost = 50;
        if ($planet['iron_amount'] >= $ironCost && $planet['crystal_amount'] >= $crystalCost) {
            researchCopper($db, $userId, $ironCost, $crystalCost, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek surovin (50000 Fe, 50 Kryst.)!']);
        }

        return true;
    }

    if ($action === 'research_drone_upgrade') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_copper']) {
            echo json_encode(['error' => 'MusÄ‚Â­ÄąË‡ mÄ‚Â­t nejdÄąâ„˘Ä‚Â­ve vyzkoumanou MĂ„â€şĂ„Ĺą!']);
            return true;
        }
        if ($planet['research_drone_upgrade']) {
            echo json_encode(['error' => 'VylepÄąË‡enÄ‚Â­ drona je jiÄąÄľ vyzkoumÄ‚Ë‡no!']);
            return true;
        }

        $copperCost = 100;
        if ($planet['res_copper'] >= $copperCost) {
            researchDroneUpgrade($db, $userId, $copperCost, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek mĂ„â€şdi (100 Cu)!']);
        }

        return true;
    }

    if ($action === 'research_drone_upgrade_2') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_copper']) {
            echo json_encode(['error' => 'MusÄ‚Â­ÄąË‡ mÄ‚Â­t nejdÄąâ„˘Ä‚Â­ve vyzkoumanou MĂ„â€şĂ„Ĺą!']);
            return true;
        }
        if ($planet['research_drone_upgrade_2']) {
            echo json_encode(['error' => 'VylepÄąË‡enÄ‚Â­ drona II je jiÄąÄľ vyzkoumÄ‚Ë‡no!']);
            return true;
        }
        if (count($planet['researched_colors']) < 2) {
            echo json_encode(['error' => 'PotÄąâ„˘ebujeÄąË‡ mÄ‚Â­t vyzkoumÄ‚Ë‡ny alespoÄąÂ 2 barvy!']);
            return true;
        }

        $copperCost = 500;
        if ($planet['res_copper'] >= $copperCost) {
            researchDroneUpgrade2($db, $userId, $copperCost, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek mĂ„â€şdi (500 Cu)!']);
        }

        return true;
    }

    if ($action === 'research_advanced_lab') {
        $planet = getPlanetData($userId, $db);
        if ($planet['research_advanced_lab']) {
            echo json_encode(['error' => 'PokroĂ„Ĺ¤ilÄ‚Ë‡ laboratoÄąâ„˘ je jiÄąÄľ vyzkoumÄ‚Ë‡na!']);
            return true;
        }
        if (count($planet['researched_colors']) < 2) {
            echo json_encode(['error' => 'PotÄąâ„˘ebujeÄąË‡ mÄ‚Â­t vyzkoumÄ‚Ë‡ny alespoÄąÂ 2 barvy!']);
            return true;
        }

        $totalColored = 0;
        foreach ($planet['alien_resources'] as $res) {
            $totalColored += $res['amount'];
        }
        if ($totalColored < 10000) {
            echo json_encode(['error' => 'PotÄąâ„˘ebujeÄąË‡ celkem 10 000 barevnÄ‚Â©ho materiÄ‚Ë‡lu!']);
            return true;
        }

        $copperCost = 5000;
        if ($planet['res_copper'] >= $copperCost) {
            researchAdvancedLab($db, $userId, $copperCost, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek mĂ„â€şdi (5000 Cu)!']);
        }

        return true;
    }

    if ($action === '__deprecated_duplicate_research_warehouse_copper__' || $action === 'research_warehouse_copper') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_advanced_lab']) {
            echo json_encode(['error' => 'MusÄ‚Â­ÄąË‡ mÄ‚Â­t PokroĂ„Ĺ¤ilou laboratoÄąâ„˘!']);
            return true;
        }
        if ($planet['warehouse_level'] < 200) {
            echo json_encode(['error' => 'Sklad ÄąÄľeleza musÄ‚Â­ bÄ‚Ëťt na Ä‚Ĺźrovni 200!']);
            return true;
        }

        $tubeCost = 2500;
        if ($planet['res_tubes'] >= $tubeCost) {
            researchWarehouseCopper($db, $userId, $tubeCost, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek zkumavek (2500)!']);
        }

        return true;
    }

    if ($action === '__deprecated_duplicate_research_drone_upgrade_3__' || $action === 'research_drone_upgrade_3') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_advanced_lab']) {
            echo json_encode(['error' => 'MusÄ‚Â­ÄąË‡ mÄ‚Â­t PokroĂ„Ĺ¤ilou laboratoÄąâ„˘!']);
            return true;
        }

        $tubeCost = 5000;
        if ($planet['res_tubes'] >= $tubeCost) {
            researchDroneUpgrade3($db, $userId, $tubeCost, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek zkumavek (5000)!']);
        }

        return true;
    }

    if ($action === '__deprecated_duplicate_research_auto_recall__' || $action === 'research_auto_recall') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_advanced_lab']) {
            echo json_encode(['error' => 'MusÄ‚Â­ÄąË‡ mÄ‚Â­t PokroĂ„Ĺ¤ilou laboratoÄąâ„˘!']);
            return true;
        }

        $tubeCost = 7500;
        if ($planet['res_tubes'] >= $tubeCost) {
            researchAutoRecall($db, $userId, $tubeCost, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek zkumavek (7500)!']);
        }

        return true;
    }

    return false;
}
