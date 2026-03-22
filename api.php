<?php
// api.php - Game logic API
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Nepřihlášen!']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

const UPGRADE_COST_MULTIPLIER = 100;

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
        
        if ($type === 'mine') $sql .= ", mine_level = ? ";
        if ($type === 'solar') $sql .= ", solar_plant_level = ? ";
        if ($type === 'warehouse') $sql .= ", warehouse_level = ? ";
        
        $sql .= " WHERE user_id = ?";
        $params[] = $newLevel;
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
    $crystalsFound = floor($secondsOut * 0.1);
    
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
