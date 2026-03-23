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
    'vehicle_recall_time' => "DATETIME",
    'researched_colors' => "TEXT DEFAULT ''",
    'res_yellow' => "REAL DEFAULT 0",
    'res_red' => "REAL DEFAULT 0",
    'res_blue' => "REAL DEFAULT 0",
    'res_green' => "REAL DEFAULT 0",
    'res_orange' => "REAL DEFAULT 0",
    'res_purple' => "REAL DEFAULT 0",
    'mine_yellow_lvl' => "INTEGER DEFAULT 0",
    'mine_red_lvl' => "INTEGER DEFAULT 0",
    'mine_blue_lvl' => "INTEGER DEFAULT 0",
    'mine_green_lvl' => "INTEGER DEFAULT 0",
    'mine_orange_lvl' => "INTEGER DEFAULT 0",
    'mine_purple_lvl' => "INTEGER DEFAULT 0",
    'has_drone' => "INTEGER DEFAULT 0",
    'drone_storage' => "REAL DEFAULT 0",
    'vehicle_sensor_lvl' => "INTEGER DEFAULT 1",
    'res_copper' => "REAL DEFAULT 0",
    'mine_copper_lvl' => "INTEGER DEFAULT 0",
    'warehouse_copper_lvl' => "INTEGER DEFAULT 0",
    'research_copper' => "INTEGER DEFAULT 0"
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
        $ironLimit = ($planet['warehouse_level'] ?? 1) * 1000;
        
        // --- New Materials Production ---
        $colors = ['yellow', 'red', 'blue', 'green', 'orange', 'purple'];
        $newResData = [];
        $totalExtraEnergyNeeded = 0;
        
        foreach ($colors as $color) {
            $lvl = $planet["mine_{$color}_lvl"] ?? 0;
            $prod = $lvl * 0.02; // 50x slower than iron (reduced 10x from 0.2)
            $newResData[$color] = [
                'lvl' => $lvl,
                'prod' => $prod,
                'amount' => $planet["res_{$color}"] ?? 0
            ];
            $totalExtraEnergyNeeded += ($lvl * 0.3); // Each alien mine consumes energy
        }
        
        // --- Copper Production ---
        $copperLvl = $planet['mine_copper_lvl'] ?? 0;
        $copperProd = $copperLvl * 0.1; // 10x slower than iron, 5x faster than alien materials
        $copperLimit = ($planet['warehouse_copper_lvl'] ?? 0) * 1000 + 500;
        $totalExtraEnergyNeeded += ($copperLvl * 0.5);

        $energyNeeded = ($ironProd * 0.5) + $totalExtraEnergyNeeded;
        $currentIron = $planet['iron_amount'] ?? 0;
        $currentEnergy = $planet['energy_amount'] ?? 0;
        $currentCopper = $planet['res_copper'] ?? 0;
        
        $productionFactor = 1.0;
        if ($energyProd < $energyNeeded) {
            $energyDiff = $energyNeeded - $energyProd;
            $secondsWithEnergy = ($energyDiff > 0) ? min($secondsElapsed, $currentEnergy / $energyDiff) : $secondsElapsed;
            $productionFactor = ($secondsWithEnergy + (($secondsElapsed - $secondsWithEnergy) * 0.1)) / $secondsElapsed;
            if ($secondsElapsed == 0) $productionFactor = 1.0;
            
            $newEnergy = $currentEnergy - ($secondsWithEnergy * $energyDiff) + (max(0, $secondsElapsed - $secondsWithEnergy) * $energyProd);
        } else {
            $newEnergy = $currentEnergy + ($secondsElapsed * ($energyProd - $energyNeeded));
        }

        $newIron = min($ironLimit, $currentIron + ($secondsElapsed * $ironProd * $productionFactor));
        $newEnergy = max(0, $newEnergy);
        $newCopper = min($copperLimit, $currentCopper + ($secondsElapsed * $copperProd * $productionFactor));

        // Update alien resources based on production factor
        $researchedStr = $planet['researched_colors'] ?? '';
        $researchedArr = $researchedStr ? explode(',', $researchedStr) : [];
        
        foreach ($colors as $color) {
            $newResData[$color]['amount'] += ($secondsElapsed * $newResData[$color]['prod'] * $productionFactor);
        }
        
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
            $armorFactor = pow($vehicleLevel, 1.2);
            $totalDamage = ($secondsSinceStart * ($baseDamageRate + ($secondsSinceStart * $acceleration))) / $armorFactor;

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
                        $sensorLvl = $planet['vehicle_sensor_lvl'] ?? 1;
                        $crystalRate = 0.1 * (1 + ($sensorLvl - 1) * 0.05);
                        $crystalsFound = floor($secondsToReturn * $crystalRate);
                        $crystalAmount += $crystalsFound;
                        $vehicleStatus = 'idle';
                        $vehicleHP = 100;
                        $db->prepare("UPDATE planets SET crystal_amount = ?, vehicle_status = 'idle', vehicle_hp = 100, last_updated = ? WHERE id = ?")->execute([$crystalAmount, date('Y-m-d H:i:s'), $planet['id']]);
                    }
                }
            }
        }
        
        // --- Drone Logic ---
        $hasDrone = $planet['has_drone'] ?? 0;
        $droneStorage = $planet['drone_storage'] ?? 0;
        if ($hasDrone) {
            $droneProdPerSec = 1 / 300; // 1 crystal / 300 seconds
            $droneStorage += ($secondsElapsed * $droneProdPerSec);
            $droneStorage = min(100, $droneStorage);
        }

        // --- PERSISTENCE: Save calculated resources back to DB ---
        $updateSql = "UPDATE planets SET 
            iron_amount = ?, energy_amount = ?, crystal_amount = ?, 
            res_yellow = ?, res_red = ?, res_blue = ?, 
            res_green = ?, res_orange = ?, res_purple = ?,
            res_copper = ?,
            drone_storage = ?,
            last_updated = ? 
            WHERE id = ?";
        
        $db->prepare($updateSql)->execute([
            $newIron, $newEnergy, $crystalAmount,
            $newResData['yellow']['amount'], $newResData['red']['amount'], $newResData['blue']['amount'],
            $newResData['green']['amount'], $newResData['orange']['amount'], $newResData['purple']['amount'],
            $newCopper,
            $droneStorage,
            date('Y-m-d H:i:s'), $planet['id']
        ]);

        return [
            'id' => $planet['id'],
            'user_id' => $planet['user_id'],
            'player_name' => $planet['player_name'],
            'iron_amount' => $newIron,
            'energy_amount' => $newEnergy,
            'crystal_amount' => $crystalAmount,
            'res_copper' => $newCopper,
            'mine_level' => $planet['mine_level'],
            'solar_plant_level' => $planet['solar_plant_level'],
            'warehouse_level' => $planet['warehouse_level'],
            'mine_copper_lvl' => $planet['mine_copper_lvl'],
            'warehouse_copper_lvl' => $planet['warehouse_copper_lvl'],
            'research_copper' => $planet['research_copper'],
            'vehicle_level' => $vehicleLevel,
            'vehicle_sensor_lvl' => $planet['vehicle_sensor_lvl'] ?? 1,
            'vehicle_hp' => $vehicleHP,
            'vehicle_status' => $vehicleStatus,
            'vehicle_start_time' => $planet['vehicle_start_time'] ?? null,
            'vehicle_recall_time' => $planet['vehicle_recall_time'] ?? null,
            'last_updated' => $planet['last_updated'],
            'iron_production' => $ironProd,
            'energy_production' => $energyProd,
            'iron_storage_limit' => $ironLimit,
            'copper_production' => $copperProd,
            'copper_storage_limit' => $copperLimit,
            'researched_colors' => $researchedArr,
            'alien_resources' => $newResData,
            'has_drone' => $hasDrone,
            'drone_storage' => $droneStorage
        ];
    } catch (Exception $e) {
        // Return basic data if complex logic fails to avoid 500 error
        return isset($planet) ? $planet : null;
    }
}



