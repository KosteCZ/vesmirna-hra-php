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
        
        $sql = "UPDATE planets SET iron_amount = ?, last_updated = ? ";
        $params = [$newIron, date('Y-m-d H:i:s')];
        
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

if ($action === 'leaderboard') {
    $stmt = $db->query("SELECT p.mine_level, p.iron_amount, u.player_name FROM planets p JOIN users u ON p.user_id = u.id ORDER BY p.mine_level DESC LIMIT 10");
    $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($leaderboard);
}
