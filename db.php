<?php
// db.php - SQLite connection and initialization

$dbPath = __DIR__ . '/game.sqlite';
$db = new PDO("sqlite:$dbPath");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Initialize tables
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    player_name TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS planets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER UNIQUE NOT NULL,
    iron_amount REAL DEFAULT 100,
    energy_amount REAL DEFAULT 50,
    mine_level INTEGER DEFAULT 1,
    solar_plant_level INTEGER DEFAULT 1,
    warehouse_level INTEGER DEFAULT 1,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id)
)");

/**
 * Get current planet data for a user
 */
function getPlanetData($userId, $db) {
    $stmt = $db->prepare("SELECT p.*, u.player_name FROM planets p JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
    $stmt->execute([$userId]);
    $planet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$planet) return null;
    
    // Calculate current resources based on elapsed time
    $now = new DateTime();
    $lastUpdate = new DateTime($planet['last_updated']);
    $secondsElapsed = $now->getTimestamp() - $lastUpdate->getTimestamp();
    
    $IRON_BASE_PROD = 1;
    $ENERGY_BASE_PROD = 2;
    $IRON_BASE_STORAGE = 500;
    
    $ironProd = $planet['mine_level'] * $IRON_BASE_PROD;
    $energyProd = $planet['solar_plant_level'] * $ENERGY_BASE_PROD;
    $ironLimit = $planet['warehouse_level'] * $IRON_BASE_STORAGE;
    
    // Simple linear production for server-side state (not full tick-by-tick simulation)
    // In real game, we'd need more complex logic to account for energy drain, but for state update:
    $newIron = min($ironLimit, $planet['iron_amount'] + ($secondsElapsed * $ironProd));
    $newEnergy = $planet['energy_amount'] + ($secondsElapsed * $energyProd);
    
    return [
        'id' => $planet['id'],
        'user_id' => $planet['user_id'],
        'player_name' => $planet['player_name'],
        'iron_amount' => $newIron,
        'energy_amount' => $newEnergy,
        'mine_level' => $planet['mine_level'],
        'solar_plant_level' => $planet['solar_plant_level'],
        'warehouse_level' => $planet['warehouse_level'],
        'last_updated' => $planet['last_updated'],
        'iron_production' => $ironProd,
        'energy_production' => $energyProd,
        'iron_storage_limit' => $ironLimit
    ];
}
