<?php
// db.php - SQLite connection and initialization

// Disable error reporting to prevent warnings from breaking JSON output
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone to UTC (industry standard for backends)
date_default_timezone_set('UTC');

$dbPath = __DIR__ . '/game.sqlite';
try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

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

// Migration: Ensure new columns exist
$columnsToAdd = [
    'crystal_amount' => "REAL DEFAULT 0",
    'vehicle_level' => "INTEGER DEFAULT 0",
    'vehicle_hp' => "REAL DEFAULT 100",
    'vehicle_status' => "TEXT DEFAULT 'idle'",
    'vehicle_start_time' => "DATETIME",
    'vehicle_recall_time' => "DATETIME"
];

$res = $db->query("PRAGMA table_info(planets)");
$existingColumns = [];
if ($res) {
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $row['name'];
    }
}

// Special migration: Rename old 'ship' columns to 'vehicle' if they exist
$renames = [
    'ship_level' => 'vehicle_level',
    'ship_hp' => 'vehicle_hp',
    'ship_status' => 'vehicle_status',
    'ship_start_time' => 'vehicle_start_time',
    'ship_recall_time' => 'vehicle_recall_time'
];

foreach ($renames as $old => $new) {
    if (in_array($old, $existingColumns) && !in_array($new, $existingColumns)) {
        try {
            $db->exec("ALTER TABLE planets RENAME COLUMN $old TO $new");
            $existingColumns[] = $new;
        } catch (Exception $e) {}
    }
}

foreach ($columnsToAdd as $col => $definition) {
    if (!in_array($col, $existingColumns)) {
        try {
            $db->exec("ALTER TABLE planets ADD COLUMN $col $definition");
        } catch (Exception $e) {}
    }
}

/**
 * Get current planet data for a user
 */
function getPlanetData($userId, $db) {
    try {
        $stmt = $db->prepare("SELECT p.*, u.player_name FROM planets p JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
        $stmt->execute([$userId]);
        $planet = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$planet) return null;
        
        $now = new DateTime();
        $lastUpdateStr = $planet['last_updated'] ?? 'now';
        $lastUpdate = new DateTime($lastUpdateStr);
        $secondsElapsed = max(0, $now->getTimestamp() - $lastUpdate->getTimestamp());
        
        $ironProd = ($planet['mine_level'] ?? 1) * 1;
        $energyProd = ($planet['solar_plant_level'] ?? 1) * 2;
        $ironLimit = ($planet['warehouse_level'] ?? 1) * 500;
        
        $energyNeeded = $ironProd * 0.5;
        $currentIron = $planet['iron_amount'] ?? 0;
        $currentEnergy = $planet['energy_amount'] ?? 0;
        
        if ($energyProd >= $energyNeeded) {
            $newIron = $currentIron + ($secondsElapsed * $ironProd);
            $newEnergy = $currentEnergy + ($secondsElapsed * ($energyProd - $energyNeeded));
        } else {
            $energyDiff = $energyNeeded - $energyProd;
            $secondsWithEnergy = ($energyDiff > 0) ? min($secondsElapsed, $currentEnergy / $energyDiff) : $secondsElapsed;
            $secondsWithoutEnergy = max(0, $secondsElapsed - $secondsWithEnergy);
            $newIron = $currentIron + ($secondsWithEnergy * $ironProd) + ($secondsWithoutEnergy * ($ironProd * 0.1));
            $newEnergy = $currentEnergy - ($secondsWithEnergy * $energyDiff) + ($secondsWithoutEnergy * $energyProd);
        }
        
        $newIron = min($ironLimit, $newIron);
        $newEnergy = max(0, $newEnergy);

        // --- Vehicle Expedition Offline Logic ---
        $vehicleStatus = $planet['vehicle_status'] ?? 'idle';
        $vehicleHP = $planet['vehicle_hp'] ?? 100;
        $vehicleLevel = $planet['vehicle_level'] ?? 0;
        $crystalAmount = $planet['crystal_amount'] ?? 0;

        if (($vehicleStatus === 'exploring' || $vehicleStatus === 'returning') && $vehicleLevel > 0) {
            $startTimeStr = $planet['vehicle_start_time'] ?? 'now';
            $startTime = new DateTime($startTimeStr);
            $secondsSinceStart = max(0, $now->getTimestamp() - $startTime->getTimestamp());
            
            $baseDamageRate = 0.1; 
            $acceleration = 0.006;
            $totalDamage = ($secondsSinceStart * ($baseDamageRate + ($secondsSinceStart * $acceleration))) / $vehicleLevel;

            $currentHP = max(0, 100 - $totalDamage);
            
            if ($currentHP <= 0) {
                $vehicleStatus = 'destroyed';
                $vehicleHP = 0;
                $vehicleLevel = 0;
                $db->prepare("UPDATE planets SET vehicle_status = 'destroyed', vehicle_level = 0, vehicle_hp = 0, last_updated = ? WHERE id = ?")->execute([date('Y-m-d H:i:s'), $planet['id']]);
            } else {
                $vehicleHP = $currentHP;
                if ($vehicleStatus === 'returning') {
                    $recallTimeStr = $planet['vehicle_recall_time'] ?? 'now';
                    $recallTime = new DateTime($recallTimeStr);
                    $secondsReturning = max(0, $now->getTimestamp() - $recallTime->getTimestamp());
                    $secondsToReturn = max(0, $recallTime->getTimestamp() - $startTime->getTimestamp());
                    
                    if ($secondsReturning >= $secondsToReturn) {
                        $crystalsFound = floor($secondsToReturn * 0.1);
                        $crystalAmount += $crystalsFound;
                        $vehicleStatus = 'idle';
                        $vehicleHP = 100;
                        $db->prepare("UPDATE planets SET crystal_amount = ?, vehicle_status = 'idle', vehicle_hp = 100, last_updated = ? WHERE id = ?")->execute([$crystalAmount, date('Y-m-d H:i:s'), $planet['id']]);
                    }
                }
            }
        }
        
        return [
            'id' => $planet['id'],
            'user_id' => $planet['user_id'],
            'player_name' => $planet['player_name'],
            'iron_amount' => $newIron,
            'energy_amount' => $newEnergy,
            'crystal_amount' => $crystalAmount,
            'mine_level' => $planet['mine_level'],
            'solar_plant_level' => $planet['solar_plant_level'],
            'warehouse_level' => $planet['warehouse_level'],
            'vehicle_level' => $vehicleLevel,
            'vehicle_hp' => $vehicleHP,
            'vehicle_status' => $vehicleStatus,
            'vehicle_start_time' => $planet['vehicle_start_time'] ?? null,
            'vehicle_recall_time' => $planet['vehicle_recall_time'] ?? null,
            'last_updated' => $planet['last_updated'],
            'iron_production' => $ironProd,
            'energy_production' => $energyProd,
            'iron_storage_limit' => $ironLimit
        ];
    } catch (Exception $e) {
        // Return basic data if complex logic fails to avoid 500 error
        return isset($planet) ? $planet : null;
    }
}



