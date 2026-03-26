<?php
// api.php - Game logic API
session_start();
require_once 'db.php';

header('Content-Type: application/json');

const UPGRADE_COST_MULTIPLIER = 100;

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
        $stmt = $db->query("SELECT p.mine_level, p.iron_amount, p.researched_colors, u.player_name FROM planets p JOIN users u ON p.user_id = u.id ORDER BY p.mine_level DESC LIMIT 10");
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
        $stmt = $db->prepare("UPDATE planets SET iron_amount = ?, energy_amount = ?, vehicle_status = 'exploring', vehicle_start_time = ?, last_updated = ? WHERE user_id = ?");
        $now = date('Y-m-d H:i:s');
        $stmt->execute([$planet['iron_amount'], $planet['energy_amount'], $now, $now, $userId]);
        echo json_encode(['success' => true]);
    }

    if ($action === 'recall_vehicle') {
        $planet = getPlanetData($userId, $db);
        $stmt = $db->prepare("UPDATE planets SET iron_amount = ?, energy_amount = ?, vehicle_status = 'returning', vehicle_recall_time = ?, last_updated = ? WHERE user_id = ?");
        $now = date('Y-m-d H:i:s');
        $stmt->execute([$planet['iron_amount'], $planet['energy_amount'], $now, $now, $userId]);
        echo json_encode(['success' => true]);
    }

    if ($action === 'finish_expedition') {
        $planet = getPlanetData($userId, $db);
        $now = new DateTime();
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
        $stmt = $db->prepare("UPDATE planets SET vehicle2_status = 'exploring', vehicle2_start_time = ?, last_updated = ? WHERE user_id = ?");
        $now = date('Y-m-d H:i:s');
        $stmt->execute([$now, $now, $userId]);
        echo json_encode(['success' => true]);
    }

    if ($action === 'recall_vehicle2') {
        $planet = getPlanetData($userId, $db);
        $stmt = $db->prepare("UPDATE planets SET vehicle2_status = 'returning', vehicle2_recall_time = ?, last_updated = ? WHERE user_id = ?");
        $now = date('Y-m-d H:i:s');
        $stmt->execute([$now, $now, $userId]);
        echo json_encode(['success' => true]);
    }

    if ($action === 'finish_expedition2') {
        $planet = getPlanetData($userId, $db);
        $now = new DateTime();
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
