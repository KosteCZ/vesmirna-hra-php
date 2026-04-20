<?php
// api.php - Game logic API
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();
require_once 'db.php';

header('Content-Type: application/json');

const UPGRADE_COST_MULTIPLIER = 100;
const ALLOWED_COLORS = ['yellow', 'red', 'blue', 'green', 'orange', 'purple'];
const ALLOWED_UPGRADE_TYPES = ['mine', 'solar', 'warehouse'];
const ROCKET_WORKSHOP_RESEARCH_COST = 15000;
const ROCKET_WORKSHOP_UPGRADE_COST = 1000000;
const ROCKET_WORKSHOP_PRODUCTION_COST = 10000;
const ROCKET_WORKSHOP_PRODUCTION_DURATION = 28800; // 8h
const ROCKET_WORKSHOP_PRODUCTION_COST_2 = 20000;
const ROCKET_WORKSHOP_PRODUCTION_DURATION_2 = 57600; // 16h

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Neprihlasen!']);
    exit;
}

try {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Nepřihlášen!']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $action = $_GET['action'] ?? '';

    // Action handlers...

    if ($action === 'get_planet') {
        $planet = getPlanetData($userId, $db);
        echo json_encode($planet);
    }

    if ($action === 'upgrade') {
        $type = $_POST['type'] ?? '';
        if (!in_array($type, ALLOWED_UPGRADE_TYPES, true)) {
            echo json_encode(['error' => 'Neplatny typ vylepseni!']);
            exit;
        }

        $planet = getPlanetData($userId, $db);
        
        if (!$planet) {
            echo json_encode(['error' => 'Planeta nenalezena!']);
            exit;
        }
        
        $currentLevel = 0;
        if ($type === 'mine') $currentLevel = $planet['mine_level'];
        if ($type === 'solar') $currentLevel = $planet['solar_plant_level'];
        if ($type === 'warehouse') $currentLevel = $planet['warehouse_level'];
        
        $cost = UPGRADE_COST_MULTIPLIER * $currentLevel;
        
        if ($planet['iron_amount'] >= $cost) {
            $newLevel = $currentLevel + 1;
            $newIron = $planet['iron_amount'] - $cost;
            
            $sql = "UPDATE planets SET iron_amount = ?, energy_amount = ?, last_updated = ? ";
            $params = [$newIron, $planet['energy_amount'], date('Y-m-d H:i:s')];
            
            if ($type === 'mine') {
                $sql .= ", mine_level = ? ";
                $params[] = $newLevel;
            }
            if ($type === 'solar') {
                $sql .= ", solar_plant_level = ? ";
                $params[] = $newLevel;
            }
            if ($type === 'warehouse') {
                if ($currentLevel >= 200) {
                    echo json_encode(['error' => 'Sklad nelze dále vylepšovat za železo (max Lvl 200)!']);
                    exit;
                }
                $sql .= ", warehouse_level = ? ";
                $params[] = $newLevel;
            }
            $sql .= " WHERE user_id = ?";
            $params[] = $userId;

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            echo json_encode(['success' => true, 'planet' => getPlanetData($userId, $db)]);
        } else {
            echo json_encode(['error' => 'Nedostatek železa!']);
        }
    }

    if ($action === 'research_color') {
        $color = $_POST['color'] ?? '';
        if (!in_array($color, ALLOWED_COLORS, true)) {
            echo json_encode(['error' => 'Neplatna barva!']);
            exit;
        }

        $planet = getPlanetData($userId, $db);
        $researched = $planet['researched_colors'] ?? [];
        
        if (count($researched) >= 2) {
            echo json_encode(['error' => 'Již máš vyzkoumány 2 barvy!']);
            exit;
        }
        
        if (in_array($color, $researched)) {
            echo json_encode(['error' => 'Tato barva je již vyzkoumána!']);
            exit;
        }
        
        $cost = (count($researched) === 0) ? 100 : 2000;
        
        if ($planet['crystal_amount'] >= $cost) {
            $researched[] = $color;
            $newList = implode(',', $researched);
            
            $stmt = $db->prepare("UPDATE planets SET crystal_amount = crystal_amount - ?, researched_colors = ?, last_updated = ? WHERE user_id = ?");
            $stmt->execute([$cost, $newList, date('Y-m-d H:i:s'), $userId]);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek krystalů!']);
        }
    }

    if ($action === 'research_copper') {
        $planet = getPlanetData($userId, $db);
        if ($planet['research_copper']) {
            echo json_encode(['error' => 'Měď je již vyzkoumána!']);
            exit;
        }
        
        // Condition: at least 2000 of any colored material
        $hasEnoughMaterial = false;
        foreach ($planet['alien_resources'] as $res) {
            if ($res['amount'] >= 2000) {
                $hasEnoughMaterial = true;
                break;
            }
        }
        
        if (!$hasEnoughMaterial) {
            echo json_encode(['error' => 'Potřebuješ alespoň 2000 jednoho druhu barevného materiálu!']);
            exit;
        }
        
        $ironCost = 50000;
        $crystalCost = 50;
        
        if ($planet['iron_amount'] >= $ironCost && $planet['crystal_amount'] >= $crystalCost) {
            $stmt = $db->prepare("UPDATE planets SET iron_amount = iron_amount - ?, crystal_amount = crystal_amount - ?, research_copper = 1, mine_copper_lvl = 1, warehouse_copper_lvl = 1, last_updated = ? WHERE user_id = ?");
            $stmt->execute([$ironCost, $crystalCost, date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek surovin (50000 Fe, 50 Kryst.)!']);
        }
    }

    if ($action === 'research_drone_upgrade') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_copper']) {
            echo json_encode(['error' => 'Musíš mít nejdříve vyzkoumanou Měď!']);
            exit;
        }
        if ($planet['research_drone_upgrade']) {
            echo json_encode(['error' => 'Vylepšení drona je již vyzkoumáno!']);
            exit;
        }
        
        $copperCost = 100;
        if ($planet['res_copper'] >= $copperCost) {
            $stmt = $db->prepare("UPDATE planets SET res_copper = res_copper - ?, research_drone_upgrade = 1, last_updated = ? WHERE user_id = ?");
            $stmt->execute([$copperCost, date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek mědi (100 Cu)!']);
        }
    }

    if ($action === 'research_drone_upgrade_2') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_copper']) {
            echo json_encode(['error' => 'Musíš mít nejdříve vyzkoumanou Měď!']);
            exit;
        }
        if ($planet['research_drone_upgrade_2']) {
            echo json_encode(['error' => 'Vylepšení drona II je již vyzkoumáno!']);
            exit;
        }
        
        // Prerequisite: 2 researched colors
        if (count($planet['researched_colors']) < 2) {
            echo json_encode(['error' => 'Potřebuješ mít vyzkoumány alespoň 2 barvy!']);
            exit;
        }

        $copperCost = 500;
        if ($planet['res_copper'] >= $copperCost) {
            $stmt = $db->prepare("UPDATE planets SET res_copper = res_copper - ?, research_drone_upgrade_2 = 1, last_updated = ? WHERE user_id = ?");
            $stmt->execute([$copperCost, date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek mědi (500 Cu)!']);
        }
    }

    if ($action === 'research_advanced_lab') {
        $planet = getPlanetData($userId, $db);
        if ($planet['research_advanced_lab']) {
            echo json_encode(['error' => 'Pokročilá laboratoř je již vyzkoumána!']);
            exit;
        }
        
        // Prerequisites: 2 colors and 10,000 total colored materials
        if (count($planet['researched_colors']) < 2) {
            echo json_encode(['error' => 'Potřebuješ mít vyzkoumány alespoň 2 barvy!']);
            exit;
        }

        $totalColored = 0;
        foreach ($planet['alien_resources'] as $res) {
            $totalColored += $res['amount'];
        }

        if ($totalColored < 10000) {
            echo json_encode(['error' => 'Potřebuješ celkem 10 000 barevného materiálu!']);
            exit;
        }

        $copperCost = 5000;
        if ($planet['res_copper'] >= $copperCost) {
            $stmt = $db->prepare("UPDATE planets SET res_copper = res_copper - ?, research_advanced_lab = 1, lab_level = 1, lab_storage_level = 1, last_updated = ? WHERE user_id = ?");
            $stmt->execute([$copperCost, date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek mědi (5000 Cu)!']);
        }
    }

    if ($action === '__deprecated_duplicate_research_warehouse_copper__') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_advanced_lab']) {
            echo json_encode(['error' => 'Musíš mít Pokročilou laboratoř!']);
            exit;
        }
        if ($planet['warehouse_level'] < 200) {
            echo json_encode(['error' => 'Sklad železa musí být na úrovni 200!']);
            exit;
        }
        
        $tubeCost = 2500;
        if ($planet['res_tubes'] >= $tubeCost) {
            $stmt = $db->prepare("UPDATE planets SET res_tubes = res_tubes - ?, research_warehouse_copper = 1, last_updated = ? WHERE user_id = ?");
            $stmt->execute([$tubeCost, date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek zkumavek (2500)!']);
        }
        exit;
    }

    if ($action === '__deprecated_duplicate_research_drone_upgrade_3__') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_advanced_lab']) {
            echo json_encode(['error' => 'Musíš mít Pokročilou laboratoř!']);
            exit;
        }
        
        $tubeCost = 5000;
        if ($planet['res_tubes'] >= $tubeCost) {
            $stmt = $db->prepare("UPDATE planets SET res_tubes = res_tubes - ?, research_drone_upgrade_3 = 1, last_updated = ? WHERE user_id = ?");
            $stmt->execute([$tubeCost, date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek zkumavek (5000)!']);
        }
        exit;
    }

    if ($action === '__deprecated_duplicate_research_auto_recall__') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_advanced_lab']) {
            echo json_encode(['error' => 'Musíš mít Pokročilou laboratoř!']);
            exit;
        }
        
        $tubeCost = 7500;
        if ($planet['res_tubes'] >= $tubeCost) {
            $stmt = $db->prepare("UPDATE planets SET res_tubes = res_tubes - ?, research_auto_recall = 1, last_updated = ? WHERE user_id = ?");
            $stmt->execute([$tubeCost, date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek zkumavek (7500)!']);
        }
        exit;
    }

    if ($action === '__deprecated_duplicate_upgrade_warehouse_copper_eff__') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_warehouse_copper']) {
            echo json_encode(['error' => 'Výzkum není dokončen!']);
            exit;
        }
        
        $cost = ($planet['warehouse_level'] + 1) * 10; // Copper cost
        if ($planet['res_copper'] >= $cost) {
            // Efficiency: +5 levels per upgrade
            $stmt = $db->prepare("UPDATE planets SET res_copper = res_copper - ?, warehouse_level = warehouse_level + 5, last_updated = ? WHERE user_id = ?");
            $stmt->execute([$cost, date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => "Nedostatek mědi ({$cost} Cu)!"]);
        }
    }

    if ($action === 'research_warehouse_copper') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_advanced_lab']) {
            echo json_encode(['error' => 'Musíš mít Pokročilou laboratoř!']);
            exit;
        }
        if ($planet['warehouse_level'] < 200) {
            echo json_encode(['error' => 'Sklad železa musí být na úrovni 200!']);
            exit;
        }
        
        $tubeCost = 2500;
        if ($planet['res_tubes'] >= $tubeCost) {
            $stmt = $db->prepare("UPDATE planets SET res_tubes = res_tubes - ?, research_warehouse_copper = 1, last_updated = ? WHERE user_id = ?");
            $stmt->execute([$tubeCost, date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek zkumavek (2500)!']);
        }
    }

    if ($action === 'research_drone_upgrade_3') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_advanced_lab']) {
            echo json_encode(['error' => 'Musíš mít Pokročilou laboratoř!']);
            exit;
        }
        
        $tubeCost = 5000;
        if ($planet['res_tubes'] >= $tubeCost) {
            $stmt = $db->prepare("UPDATE planets SET res_tubes = res_tubes - ?, research_drone_upgrade_3 = 1, last_updated = ? WHERE user_id = ?");
            $stmt->execute([$tubeCost, date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek zkumavek (5000)!']);
        }
    }

    if ($action === 'research_auto_recall') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_advanced_lab']) {
            echo json_encode(['error' => 'Musíš mít Pokročilou laboratoř!']);
            exit;
        }
        
        $tubeCost = 7500;
        if ($planet['res_tubes'] >= $tubeCost) {
            $stmt = $db->prepare("UPDATE planets SET res_tubes = res_tubes - ?, research_auto_recall = 1, last_updated = ? WHERE user_id = ?");
            $stmt->execute([$tubeCost, date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek zkumavek (7500)!']);
        }
    }

    if ($action === 'research_rocket_workshop') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_advanced_lab']) {
            echo json_encode(['error' => 'Musis mit Pokrocilou laborator!']);
            exit;
        }
        if ($planet['research_rocket_workshop']) {
            echo json_encode(['error' => 'Raketova dilna je jiz postavena!']);
            exit;
        }

        if ($planet['res_tubes'] >= ROCKET_WORKSHOP_RESEARCH_COST) {
            $stmt = $db->prepare("UPDATE planets SET res_tubes = res_tubes - ?, research_rocket_workshop = 1, rocket_workshop_level = 1, rocket_workshop_status = 'idle', rocket_workshop_mode = 1, rocket_workshop_started_at = NULL, rocket_workshop_ready_at = NULL, last_updated = ? WHERE user_id = ?");
            $stmt->execute([ROCKET_WORKSHOP_RESEARCH_COST, date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek zkumavek (15000)!']);
        }
    }

    if ($action === 'upgrade_rocket_workshop') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_rocket_workshop']) {
            echo json_encode(['error' => 'Raketova dilna neni postavena!']);
            exit;
        }
        if (($planet['rocket_workshop_level'] ?? 1) >= 2) {
            echo json_encode(['error' => 'Raketova dilna je jiz na maximalni urovni!']);
            exit;
        }

        if ($planet['iron_amount'] >= ROCKET_WORKSHOP_UPGRADE_COST) {
            $stmt = $db->prepare("UPDATE planets SET iron_amount = iron_amount - ?, rocket_workshop_level = 2, last_updated = ? WHERE user_id = ?");
            $stmt->execute([ROCKET_WORKSHOP_UPGRADE_COST, date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek zeleza (1 000 000 Fe)!']);
        }
    }

    if ($action === 'start_rocket_workshop_production') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_rocket_workshop']) {
            echo json_encode(['error' => 'Raketova dilna neni postavena!']);
            exit;
        }

        $mode = isset($_POST['mode']) ? (int)$_POST['mode'] : 1;
        
        // Slot selection based on mode
        $statusCol = ($mode === 2) ? 'rocket_workshop_2_status' : 'rocket_workshop_status';
        $startCol = ($mode === 2) ? 'rocket_workshop_2_started_at' : 'rocket_workshop_started_at';
        $readyCol = ($mode === 2) ? 'rocket_workshop_2_ready_at' : 'rocket_workshop_ready_at';
        
        if ($mode === 2 && ($planet['rocket_workshop_level'] ?? 1) < 2) {
            echo json_encode(['error' => 'Musis vylepsit dilnu pro tento typ vyroby!']);
            exit;
        }

        $rocketParts = $planet['rocket_parts'] ?? getDefaultRocketPartsInventory();
        $availableParts = array_filter($rocketParts, static fn ($count) => $count < 10);
        if (count($availableParts) === 0) {
            echo json_encode(['error' => 'Uz mas vyrobeno vse 10x, dalsi vyroba neni mozna.']);
            exit;
        }

        if (($planet[$statusCol] ?? 'idle') === 'producing') {
            echo json_encode(['error' => 'V tomto slotu vyroba uz probiha!']);
            exit;
        }
        if (($planet[$statusCol] ?? 'idle') === 'ready') {
            echo json_encode(['error' => 'Nejdriv si vyzvedni hotovy vytvor v tomto slotu.']);
            exit;
        }

        $cost = ($mode === 2) ? ROCKET_WORKSHOP_PRODUCTION_COST_2 : ROCKET_WORKSHOP_PRODUCTION_COST;
        $duration = ($mode === 2) ? ROCKET_WORKSHOP_PRODUCTION_DURATION_2 : ROCKET_WORKSHOP_PRODUCTION_DURATION;

        if ($planet['res_tubes'] < $cost) {
            echo json_encode(['error' => "Nedostatek zkumavek ({$cost})!"]);
            exit;
        }

        $startedAt = new DateTime('now', new DateTimeZone('UTC'));
        $readyAt = clone $startedAt;
        $readyAt->modify('+' . $duration . ' seconds');

        $stmt = $db->prepare("UPDATE planets SET res_tubes = res_tubes - ?, $statusCol = 'producing', $startCol = ?, $readyCol = ?, last_updated = ? WHERE user_id = ?");
        $stmt->execute([
            $cost,
            $startedAt->format('Y-m-d H:i:s'),
            $readyAt->format('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
            $userId
        ]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'collect_rocket_workshop_product') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_rocket_workshop']) {
            echo json_encode(['error' => 'Raketova dilna neni postavena!']);
            exit;
        }

        $slot = isset($_POST['slot']) ? (int)$_POST['slot'] : 1;
        $statusCol = ($slot === 2) ? 'rocket_workshop_2_status' : 'rocket_workshop_status';
        $startCol = ($slot === 2) ? 'rocket_workshop_2_started_at' : 'rocket_workshop_started_at';
        $readyCol = ($slot === 2) ? 'rocket_workshop_2_ready_at' : 'rocket_workshop_ready_at';

        if (($planet[$statusCol] ?? 'idle') !== 'ready') {
            echo json_encode(['error' => 'V tomto slotu zatim neni nic k vyzvednuti.']);
            exit;
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
            $stmt = $db->prepare("UPDATE planets SET $statusCol = 'idle', $startCol = NULL, $readyCol = NULL, last_updated = ? WHERE id = ?");
            $stmt->execute([date('Y-m-d H:i:s'), $planet['id']]);
            echo json_encode(['error' => 'Uz mas vyrobeno vse 10x, dalsi vyroba neni mozna.']);
            exit;
        }

        $stmt = $db->prepare("UPDATE planets SET rocket_parts = ?, $statusCol = 'idle', $startCol = NULL, $readyCol = NULL, last_updated = ? WHERE user_id = ?");
        $stmt->execute([
            json_encode($rocketParts),
            date('Y-m-d H:i:s'),
            $userId
        ]);

        echo json_encode([
            'success' => true,
            'parts' => $selectedParts,
            'part_label' => implode(' a ', $selectedParts)
        ]);
        exit;
    }

    if ($action === 'upgrade_warehouse_copper_eff') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_warehouse_copper']) {
            echo json_encode(['error' => 'Výzkum není dokončen!']);
            exit;
        }
        
        $cost = ($planet['warehouse_level'] + 1) * 10; // Copper cost
        if ($planet['res_copper'] >= $cost) {
            // Efficiency: +5 levels per upgrade
            $stmt = $db->prepare("UPDATE planets SET res_copper = res_copper - ?, warehouse_level = warehouse_level + 5, last_updated = ? WHERE user_id = ?");
            $stmt->execute([$cost, date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => "Nedostatek mědi ({$cost} Cu)!"]);
        }
    }

    if ($action === 'upgrade_lab') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_advanced_lab']) {
            echo json_encode(['error' => 'Laboratoř není vyzkoumána!']);
            exit;
        }
        
        $currentLevel = $planet['lab_level'];
        $ironCost = ($currentLevel + 1) * 5000;
        $crystalCost = ($currentLevel + 1) * 100;
        
        if ($planet['iron_amount'] >= $ironCost && $planet['crystal_amount'] >= $crystalCost) {
            $stmt = $db->prepare("UPDATE planets SET iron_amount = iron_amount - ?, crystal_amount = crystal_amount - ?, lab_level = lab_level + 1, last_updated = ? WHERE user_id = ?");
            $stmt->execute([$ironCost, $crystalCost, date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek surovin!']);
        }
    }

    if ($action === 'upgrade_lab_storage') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_advanced_lab']) {
            echo json_encode(['error' => 'Laboratoř není vyzkoumána!']);
            exit;
        }
        
        $currentLevel = $planet['lab_storage_level'];
        $ironCost = ($currentLevel + 1) * 8000;
        $crystalCost = ($currentLevel + 1) * 150;
        
        if ($planet['iron_amount'] >= $ironCost && $planet['crystal_amount'] >= $crystalCost) {
            $stmt = $db->prepare("UPDATE planets SET iron_amount = iron_amount - ?, crystal_amount = crystal_amount - ?, lab_storage_level = lab_storage_level + 1, last_updated = ? WHERE user_id = ?");
            $stmt->execute([$ironCost, $crystalCost, date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek surovin!']);
        }
    }

    if ($action === 'upgrade_copper_mine') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_copper']) {
            echo json_encode(['error' => 'Měď není vyzkoumána!']);
            exit;
        }
        
        $currentLevel = $planet['mine_copper_lvl'];
        $ironCost = ($currentLevel + 1) * 1000;
        $crystalCost = ($currentLevel + 1) * 10;
        
        if ($planet['iron_amount'] >= $ironCost && $planet['crystal_amount'] >= $crystalCost) {
            $stmt = $db->prepare("UPDATE planets SET iron_amount = iron_amount - ?, crystal_amount = crystal_amount - ?, mine_copper_lvl = mine_copper_lvl + 1, last_updated = ? WHERE user_id = ?");
            $stmt->execute([$ironCost, $crystalCost, date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek surovin!']);
        }
    }

    if ($action === 'upgrade_copper_warehouse') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_copper']) {
            echo json_encode(['error' => 'Měď není vyzkoumána!']);
            exit;
        }
        
        $currentLevel = $planet['warehouse_copper_lvl'];
        $ironCost = ($currentLevel + 1) * 2000;
        $crystalCost = ($currentLevel + 1) * 20;
        
        if ($planet['iron_amount'] >= $ironCost && $planet['crystal_amount'] >= $crystalCost) {
            $stmt = $db->prepare("UPDATE planets SET iron_amount = iron_amount - ?, crystal_amount = crystal_amount - ?, warehouse_copper_lvl = warehouse_copper_lvl + 1, last_updated = ? WHERE user_id = ?");
            $stmt->execute([$ironCost, $crystalCost, date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek surovin!']);
        }
    }

    if ($action === 'upgrade_alien_mine') {
        $color = $_POST['color'] ?? '';
        if (!in_array($color, ALLOWED_COLORS, true)) {
            echo json_encode(['error' => 'Neplatna barva!']);
            exit;
        }

        $planet = getPlanetData($userId, $db);
        $researched = $planet['researched_colors'] ?? [];
        
        if (!in_array($color, $researched)) {
            echo json_encode(['error' => 'Tato barva není vyzkoumána!']);
            exit;
        }
        
        $currentLevel = $planet['alien_resources'][$color]['lvl'];
        $ironCost = ($currentLevel + 1) * 500;
        $crystalCost = ($currentLevel + 1) * 50;
        
        if ($planet['iron_amount'] >= $ironCost && $planet['crystal_amount'] >= $crystalCost) {
            $newIron = $planet['iron_amount'] - $ironCost;
            $newCrystals = $planet['crystal_amount'] - $crystalCost;
            $newLevel = $currentLevel + 1;
            
            $sql = "UPDATE planets SET iron_amount = ?, crystal_amount = ?, mine_{$color}_lvl = ?, last_updated = ? WHERE user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$newIron, $newCrystals, $newLevel, date('Y-m-d H:i:s'), $userId]);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek surovin (Fe nebo krystaly)!']);
        }
    }

    if ($action === 'leaderboard') {
        $stmt = $db->query("SELECT p.mine_level, p.iron_amount, p.researched_colors, u.player_name, u.last_login FROM planets p JOIN users u ON p.user_id = u.id ORDER BY p.mine_level DESC LIMIT 10");
        $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($leaderboard);
    }

    if ($action === 'global_stats') {
        $sql = "SELECT 
            SUM(res_yellow) as yellow, SUM(res_red) as red, SUM(res_blue) as blue, 
            SUM(res_green) as green, SUM(res_orange) as orange, SUM(res_purple) as purple 
            FROM planets";
        $stmt = $db->query($sql);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    }

    if ($action === 'buy_vehicle') {
        $planet = getPlanetData($userId, $db);
        if ($planet['vehicle_level'] > 0 && $planet['vehicle_status'] !== 'destroyed') {
            echo json_encode(['error' => 'Vozidlo uz mas!']);
            exit;
        }

        if ($planet['iron_amount'] >= 500) {
            $newIron = $planet['iron_amount'] - 500;
            $stmt = $db->prepare("UPDATE planets SET iron_amount = ?, energy_amount = ?, vehicle_level = 1, vehicle_hp = 100, vehicle_status = 'idle', last_updated = ? WHERE user_id = ?");
            $stmt->execute([$newIron, $planet['energy_amount'], date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek železa!']);
        }
    }

    if ($action === 'start_expedition') {
        $planet = getPlanetData($userId, $db);
        if (($planet['vehicle_level'] ?? 0) <= 0) {
            echo json_encode(['error' => 'Nejdriv musis koupit vozidlo!']);
            exit;
        }
        if (($planet['vehicle_status'] ?? 'idle') !== 'idle') {
            echo json_encode(['error' => 'Vozidlo uz je na misi nebo mimo provoz!']);
            exit;
        }

        $stmt = $db->prepare("UPDATE planets SET iron_amount = ?, energy_amount = ?, vehicle_status = 'exploring', vehicle_start_time = ?, last_updated = ? WHERE user_id = ?");
        $now = date('Y-m-d H:i:s');
        $stmt->execute([$planet['iron_amount'], $planet['energy_amount'], $now, $now, $userId]);
        echo json_encode(['success' => true]);
    }

    if ($action === 'recall_vehicle') {
        $planet = getPlanetData($userId, $db);
        if (($planet['vehicle_status'] ?? '') !== 'exploring') {
            echo json_encode(['error' => 'Vozidlo zrovna neni na pruzkumu!']);
            exit;
        }

        $stmt = $db->prepare("UPDATE planets SET iron_amount = ?, energy_amount = ?, vehicle_status = 'returning', vehicle_recall_time = ?, last_updated = ? WHERE user_id = ?");
        $now = date('Y-m-d H:i:s');
        $stmt->execute([$planet['iron_amount'], $planet['energy_amount'], $now, $now, $userId]);
        echo json_encode(['success' => true]);
    }

    if ($action === 'finish_expedition') {
        $planet = getPlanetData($userId, $db);
        if (($planet['vehicle_status'] ?? '') !== 'returning' || empty($planet['vehicle_start_time']) || empty($planet['vehicle_recall_time'])) {
            echo json_encode(['error' => 'Expedici nelze dokoncit v aktualnim stavu!']);
            exit;
        }

        $startTime = new DateTime($planet['vehicle_start_time']);
        $recallTime = new DateTime($planet['vehicle_recall_time']);
        
        $secondsOut = $recallTime->getTimestamp() - $startTime->getTimestamp();
        $sensorLvl = $planet['vehicle_sensor_lvl'] ?? 1;
        $timeBonus = 1 + ($secondsOut * 0.0005);
        $crystalRate = 0.1 * (1 + ($sensorLvl - 1) * 0.05) * $timeBonus;
        $crystalsFound = floor($secondsOut * $crystalRate);
        
        $stmt = $db->prepare("UPDATE planets SET iron_amount = ?, energy_amount = ?, crystal_amount = crystal_amount + ?, vehicle_status = 'idle', vehicle_hp = 100, last_updated = ? WHERE user_id = ?");
        $stmt->execute([$planet['iron_amount'], $planet['energy_amount'], $crystalsFound, date('Y-m-d H:i:s'), $userId]);
        echo json_encode(['success' => true]);
    }

    if ($action === 'destroy_vehicle') {
        $planet = getPlanetData($userId, $db);
        $stmt = $db->prepare("UPDATE planets SET iron_amount = ?, energy_amount = ?, vehicle_status = 'destroyed', vehicle_level = 0, last_updated = ? WHERE user_id = ?");
        $stmt->execute([$planet['iron_amount'], $planet['energy_amount'], date('Y-m-d H:i:s'), $userId]);
        echo json_encode(['success' => true]);
    }

    if ($action === 'upgrade_vehicle') {
        $planet = getPlanetData($userId, $db);
        $cost = ($planet['vehicle_level'] + 1) * 500;
        
        if ($planet['iron_amount'] >= $cost) {
            $newIron = $planet['iron_amount'] - $cost;
            $stmt = $db->prepare("UPDATE planets SET iron_amount = ?, energy_amount = ?, vehicle_level = vehicle_level + 1, last_updated = ? WHERE user_id = ?");
            $stmt->execute([$newIron, $planet['energy_amount'], date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek železa!']);
        }
    }

    if ($action === 'upgrade_vehicle_sensors') {
        $planet = getPlanetData($userId, $db);
        $cost = $planet['vehicle_sensor_lvl'] * 1000;
        
        if ($planet['iron_amount'] >= $cost) {
            $newIron = $planet['iron_amount'] - $cost;
            $stmt = $db->prepare("UPDATE planets SET iron_amount = ?, energy_amount = ?, vehicle_sensor_lvl = vehicle_sensor_lvl + 1, last_updated = ? WHERE user_id = ?");
            $stmt->execute([$newIron, $planet['energy_amount'], date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => "Nedostatek železa ({$cost})!"]);
        }
    }

    if ($action === 'buy_vehicle2') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_copper']) {
            echo json_encode(['error' => 'Měď není vyzkoumána!']);
            exit;
        }
        if ($planet['vehicle2_level'] > 0 && $planet['vehicle2_status'] !== 'destroyed') {
            echo json_encode(['error' => 'Druhe vozidlo uz mas!']);
            exit;
        }
        if ($planet['res_copper'] >= 500) {
            $stmt = $db->prepare("UPDATE planets SET res_copper = res_copper - 500, vehicle2_level = 1, vehicle2_hp = 100, vehicle2_status = 'idle', last_updated = ? WHERE user_id = ?");
            $stmt->execute([date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek mědi (500 Cu)!']);
        }
    }

    if ($action === 'start_expedition2') {
        $planet = getPlanetData($userId, $db);
        if (($planet['vehicle2_level'] ?? 0) <= 0) {
            echo json_encode(['error' => 'Nejdriv musis koupit druhe vozidlo!']);
            exit;
        }
        if (($planet['vehicle2_status'] ?? 'idle') !== 'idle') {
            echo json_encode(['error' => 'Druhe vozidlo uz je na misi nebo mimo provoz!']);
            exit;
        }
        $stmt = $db->prepare("UPDATE planets SET vehicle2_status = 'exploring', vehicle2_start_time = ?, last_updated = ? WHERE user_id = ?");
        $now = date('Y-m-d H:i:s');
        $stmt->execute([$now, $now, $userId]);
        echo json_encode(['success' => true]);
    }

    if ($action === 'recall_vehicle2') {
        $planet = getPlanetData($userId, $db);
        if (($planet['vehicle2_status'] ?? '') !== 'exploring') {
            echo json_encode(['error' => 'Druhe vozidlo zrovna neni na pruzkumu!']);
            exit;
        }
        $stmt = $db->prepare("UPDATE planets SET vehicle2_status = 'returning', vehicle2_recall_time = ?, last_updated = ? WHERE user_id = ?");
        $now = date('Y-m-d H:i:s');
        $stmt->execute([$now, $now, $userId]);
        echo json_encode(['success' => true]);
    }

    if ($action === 'finish_expedition2') {
        $planet = getPlanetData($userId, $db);
        if (($planet['vehicle2_status'] ?? '') !== 'returning' || empty($planet['vehicle2_start_time']) || empty($planet['vehicle2_recall_time'])) {
            echo json_encode(['error' => 'Expedici druheho vozidla nelze dokoncit v aktualnim stavu!']);
            exit;
        }
        $startTime = new DateTime($planet['vehicle2_start_time']);
        $recallTime = new DateTime($planet['vehicle2_recall_time']);
        $secondsOut = $recallTime->getTimestamp() - $startTime->getTimestamp();
        $sensorLvl = $planet['vehicle2_sensor_lvl'] ?? 1;
        $timeBonus = 1 + ($secondsOut * 0.0005);
        // Sensors are 2x more effective (10% bonus instead of 5%)
        $crystalRate = 0.2 * (1 + ($sensorLvl - 1) * 0.10) * $timeBonus;
        $crystalsFound = floor($secondsOut * $crystalRate);
        $stmt = $db->prepare("UPDATE planets SET crystal_amount = crystal_amount + ?, vehicle2_status = 'idle', vehicle2_hp = 100, last_updated = ? WHERE user_id = ?");
        $stmt->execute([$crystalsFound, date('Y-m-d H:i:s'), $userId]);
        echo json_encode(['success' => true]);
    }

    if ($action === 'destroy_vehicle2') {
        $planet = getPlanetData($userId, $db);
        $stmt = $db->prepare("UPDATE planets SET vehicle2_status = 'destroyed', vehicle2_level = 0, last_updated = ? WHERE user_id = ?");
        $stmt->execute([date('Y-m-d H:i:s'), $userId]);
        echo json_encode(['success' => true]);
    }

    if ($action === 'upgrade_vehicle2_armor') {
        $planet = getPlanetData($userId, $db);
        $cost = ($planet['vehicle2_level'] + 1) * 100;
        if ($planet['res_copper'] >= $cost) {
            $stmt = $db->prepare("UPDATE planets SET res_copper = res_copper - ?, vehicle2_level = vehicle2_level + 1, last_updated = ? WHERE user_id = ?");
            $stmt->execute([$cost, date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => "Nedostatek mědi ({$cost} Cu)!"]);
        }
    }

    if ($action === 'upgrade_vehicle2_sensors') {
        $planet = getPlanetData($userId, $db);
        $cost = $planet['vehicle2_sensor_lvl'] * 150;
        if ($planet['res_copper'] >= $cost) {
            $stmt = $db->prepare("UPDATE planets SET res_copper = res_copper - ?, vehicle2_sensor_lvl = vehicle2_sensor_lvl + 1, last_updated = ? WHERE user_id = ?");
            $stmt->execute([$cost, date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => "Nedostatek mědi ({$cost} Cu)!"]);
        }
    }

    if ($action === 'buy_drone') {
        $planet = getPlanetData($userId, $db);
        if ($planet['has_drone']) {
            echo json_encode(['error' => 'Drona již máš!']);
            exit;
        }
        
        if ($planet['crystal_amount'] >= 250) {
            $stmt = $db->prepare("UPDATE planets SET crystal_amount = crystal_amount - 250, has_drone = 1, last_updated = ? WHERE user_id = ?");
            $stmt->execute([date('Y-m-d H:i:s'), $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek krystalů (250)!']);
        }
    }

    if ($action === 'collect_drone') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['has_drone']) {
            echo json_encode(['error' => 'Nemáš drona!']);
            exit;
        }
        
        $amount = $planet['drone_storage'];
        $stmt = $db->prepare("UPDATE planets SET crystal_amount = crystal_amount + ?, drone_storage = 0, last_updated = ? WHERE user_id = ?");
        $stmt->execute([$amount, date('Y-m-d H:i:s'), $userId]);
        echo json_encode(['success' => true]);
    }

} catch (Exception $e) {
    echo json_encode(['error' => 'Chyba serveru: ' . $e->getMessage()]);
}
