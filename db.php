<?php
// db.php - SQLite connection and initialization

// Disable error reporting to prevent warnings from breaking JSON output
error_reporting(E_ALL);
ini_set('display_errors', 0); // Production: hide errors, they will be in logs or handled by try-catch

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
$resUsers = $db->query("PRAGMA table_info(users)");
$existingUserColumns = [];
if ($resUsers) {
    while ($row = $resUsers->fetch(PDO::FETCH_ASSOC)) {
        $existingUserColumns[] = $row['name'];
    }
}
if (!in_array('last_login', $existingUserColumns)) {
    $db->exec("ALTER TABLE users ADD COLUMN last_login DATETIME");
}

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
    'research_copper' => "INTEGER DEFAULT 0",
    'research_drone_upgrade' => "INTEGER DEFAULT 0",
    'research_drone_upgrade_2' => "INTEGER DEFAULT 0",
    'vehicle2_level' => "INTEGER DEFAULT 0",
    'vehicle2_hp' => "REAL DEFAULT 100",
    'vehicle2_status' => "TEXT DEFAULT 'idle'",
    'vehicle2_sensor_lvl' => "INTEGER DEFAULT 1",
    'vehicle2_start_time' => "DATETIME",
    'vehicle2_recall_time' => "DATETIME",
    'res_tubes' => "REAL DEFAULT 0",
    'lab_level' => "INTEGER DEFAULT 0",
    'lab_storage_level' => "INTEGER DEFAULT 0",
    'research_advanced_lab' => "INTEGER DEFAULT 0",
    'research_warehouse_copper' => "INTEGER DEFAULT 0",
    'research_drone_upgrade_3' => "INTEGER DEFAULT 0",
    'research_auto_recall' => "INTEGER DEFAULT 0",
    'research_rocket_workshop' => "INTEGER DEFAULT 0",
    'rocket_workshop_level' => "INTEGER DEFAULT 0",
    'rocket_workshop_status' => "TEXT DEFAULT 'idle'",
    'rocket_workshop_mode' => "INTEGER DEFAULT 1",
    'rocket_workshop_started_at' => "DATETIME",
    'rocket_workshop_ready_at' => "DATETIME",
    'rocket_workshop_2_status' => "TEXT DEFAULT 'idle'",
    'rocket_workshop_2_started_at' => "DATETIME",
    'rocket_workshop_2_ready_at' => "DATETIME",
    'research_alien_slot_3' => "INTEGER DEFAULT 0",
    'rocket_parts' => "TEXT DEFAULT ''"
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

function isReadonlyDatabaseError(Throwable $e): bool {
    return $e instanceof PDOException && stripos($e->getMessage(), 'readonly database') !== false;
}

function safePlanetWrite(PDO $db, string $sql, array $params): bool {
    try {
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    } catch (Throwable $e) {
        if (isReadonlyDatabaseError($e)) {
            return false;
        }

        throw $e;
    }
}

/**
 * Calculates the number of seconds until a vehicle reaches a certain damage threshold.
 * Uses the formula: totalDamage = (seconds * (baseRate + seconds * acceleration)) / armorFactor
 */
function calculateSafeTime($targetDamage, $baseRate, $acceleration, $armorFactor) {
    // Formula for seconds: ( -baseRate + sqrt(baseRate^2 + 4 * acceleration * targetDamage * armorFactor) ) / (2 * acceleration)
    $inner = pow($baseRate, 2) + 4 * $acceleration * $targetDamage * $armorFactor;
    if ($inner < 0) return 0;
    return (-$baseRate + sqrt($inner)) / (2 * $acceleration);
}

function getRocketPartDefinitions(): array {
    return [
        'rocket_tip' => "\u{0160}pi\u{010d}ka rakety",
        'rocket_body' => 'Trup rakety',
        'fuel_tank' => "Palivov\u{00e9} n\u{00e1}dr\u{017e}e",
        'jet_engine' => "Tryskov\u{00fd} motor",
        'satellite' => 'Satelit',
        'solar_panel' => "Sol\u{00e1}rn\u{00ed} panel",
        'seat' => 'Sedadlo',
        'fuel_canister' => "Kanystr s palivem",
        'electronics' => "Elektronick\u{00e9} za\u{0159}\u{00ed}zen\u{00ed}",
        'tools' => "N\u{00e1}\u{0159}ad\u{00ed}"
    ];
}

function getDefaultRocketPartsInventory(): array {
    $inventory = [];
    foreach (getRocketPartDefinitions() as $key => $_label) {
        $inventory[$key] = 0;
    }

    return $inventory;
}

function normalizeRocketPartsInventory($rawInventory): array {
    $inventory = getDefaultRocketPartsInventory();
    if (!is_string($rawInventory) || $rawInventory === '') {
        return $inventory;
    }

    $decoded = json_decode($rawInventory, true);
    if (!is_array($decoded)) {
        return $inventory;
    }

    foreach ($inventory as $key => $defaultValue) {
        $value = $decoded[$key] ?? $defaultValue;
        $inventory[$key] = max(0, min(10, (int) $value));
    }

    return $inventory;
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

        // --- Advanced Lab: Test Tubes Production ---
        $labLvl = $planet['lab_level'] ?? 0;
        $labStorageLvl = $planet['lab_storage_level'] ?? 0;
        $tubeProd = $labLvl * 0.05; // Base production rate
        $tubeLimit = ($labStorageLvl > 0) ? ($labStorageLvl * 500) : 100;
        $currentTubes = $planet['res_tubes'] ?? 0;
        $rocketParts = normalizeRocketPartsInventory($planet['rocket_parts'] ?? '');
        $availableRocketParts = [];
        foreach ($rocketParts as $partKey => $partCount) {
            if ($partCount < 10) {
                $availableRocketParts[] = $partKey;
            }
        }
        
        if (($planet['research_advanced_lab'] ?? 0)) {
            $totalExtraEnergyNeeded += ($labLvl * 1.5);
        }

        $energyNeeded = ($ironProd * 0.5) + $totalExtraEnergyNeeded;
        $currentIron = $planet['iron_amount'] ?? 0;
        $currentEnergy = $planet['energy_amount'] ?? 0;
        $currentCopper = $planet['res_copper'] ?? 0;
        $currentTubes = $planet['res_tubes'] ?? 0;
        
        $productionFactor = 1.0;
        if ($energyProd < $energyNeeded) {
            $energyDiff = $energyNeeded - $energyProd;
            $secondsWithEnergy = ($energyDiff > 0) ? min($secondsElapsed, $currentEnergy / $energyDiff) : $secondsElapsed;
            if ($secondsElapsed > 0) {
                $productionFactor = ($secondsWithEnergy + (($secondsElapsed - $secondsWithEnergy) * 0.1)) / $secondsElapsed;
            }
            
            $newEnergy = $currentEnergy - ($secondsWithEnergy * $energyDiff) + (max(0, $secondsElapsed - $secondsWithEnergy) * $energyProd);
        } else {
            $newEnergy = $currentEnergy + ($secondsElapsed * ($energyProd - $energyNeeded));
        }

        $newIron = min($ironLimit, $currentIron + ($secondsElapsed * $ironProd * $productionFactor));
        $newEnergy = max(0, $newEnergy);
        $newCopper = min($copperLimit, $currentCopper + ($secondsElapsed * $copperProd * $productionFactor));
        $newTubes = min($tubeLimit, $currentTubes + ($secondsElapsed * $tubeProd * $productionFactor));

        // Update alien resources based on production factor
        $researchedStr = $planet['researched_colors'] ?? '';
        $researchedArr = $researchedStr ? explode(',', $researchedStr) : [];
        
        foreach ($colors as $color) {
            $newResData[$color]['amount'] += ($secondsElapsed * $newResData[$color]['prod'] * $productionFactor);
        }
        
        // --- Vehicle 1 Expedition Offline Logic ---
        $vehicleStatus = $planet['vehicle_status'] ?? 'idle';
        $vehicleHP = $planet['vehicle_hp'] ?? 100;
        $vehicleLevel = $planet['vehicle_level'] ?? 0;
        $crystalAmount = $planet['crystal_amount'] ?? 0;

        if (($vehicleStatus === 'exploring' || $vehicleStatus === 'returning') && $vehicleLevel > 0) {
            $startTimeStr = $planet['vehicle_start_time'] ?? 'now';
            $startTime = new DateTime($startTimeStr);
            $secondsSinceStart = max(0, $now->getTimestamp() - $startTime->getTimestamp());
            $damageSeconds = $secondsSinceStart;

            if ($vehicleStatus === 'returning') {
                $recallTimeStr = $planet['vehicle_recall_time'] ?? 'now';
                $recallTime = new DateTime($recallTimeStr);
                $secondsToReturn = max(0, $recallTime->getTimestamp() - $startTime->getTimestamp());
                $missionCompleteAfter = $secondsToReturn * 2;
                $damageSeconds = min($secondsSinceStart, $missionCompleteAfter);
            }
            
            $baseDamageRate = 0.1; 
            $acceleration = 0.003;
            $armorFactor = pow($vehicleLevel, 1.2);
            $totalDamage = ($damageSeconds * ($baseDamageRate + ($damageSeconds * $acceleration))) / $armorFactor;

            // Auto-Recall Logic (Offline)
            $hasAutoRecall = $planet['research_auto_recall'] ?? 0;
            if ($hasAutoRecall && $vehicleStatus === 'exploring' && $totalDamage >= 10) {
                // Vehicle should have recalled at 90 HP (10 damage)
                $safeSeconds = calculateSafeTime(10, $baseDamageRate, $acceleration, $armorFactor);
                
                $recallTime = clone $startTime;
                $recallTime->modify('+' . round($safeSeconds) . ' seconds');
                
                // Recalculate based on safe recall
                $damageSeconds = $safeSeconds; // Damage stops at recall point
                $totalDamage = 10;
                $vehicleStatus = 'returning';
                
                safePlanetWrite($db, "UPDATE planets SET vehicle_status = 'returning', vehicle_recall_time = ?, last_updated = ? WHERE id = ?", [
                    $recallTime->format('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s'),
                    $planet['id']
                ]);
            }

            $currentHP = max(0, 100 - $totalDamage);
            
            if ($currentHP <= 0) {
                $vehicleStatus = 'destroyed';
                $vehicleHP = 0;
                $vehicleLevel = 0;
                safePlanetWrite($db, "UPDATE planets SET vehicle_status = 'destroyed', vehicle_level = 0, vehicle_hp = 0, last_updated = ? WHERE id = ?", [date('Y-m-d H:i:s'), $planet['id']]);
            } else {
                $vehicleHP = $currentHP;
                if ($vehicleStatus === 'returning') {
                    $recallTimeStr = $planet['vehicle_recall_time'] ?? 'now';
                    $recallTime = new DateTime($recallTimeStr);
                    $secondsReturning = max(0, $now->getTimestamp() - $recallTime->getTimestamp());
                    $secondsToReturn = max(0, $recallTime->getTimestamp() - $startTime->getTimestamp());
                    
                    if ($secondsReturning >= $secondsToReturn) {
                        $sensorLvl = $planet['vehicle_sensor_lvl'] ?? 1;
                        $timeBonus = 1 + ($secondsToReturn * 0.0005); // +5% bonus every 1000 seconds
                        $crystalRate = 0.1 * (1 + ($sensorLvl - 1) * 0.05) * $timeBonus;
                        $crystalsFound = floor($secondsToReturn * $crystalRate);
                        $crystalAmount += $crystalsFound;
                        $vehicleStatus = 'idle';
                        $vehicleHP = 100;
                        safePlanetWrite($db, "UPDATE planets SET crystal_amount = ?, vehicle_status = 'idle', vehicle_hp = 100, last_updated = ? WHERE id = ?", [$crystalAmount, date('Y-m-d H:i:s'), $planet['id']]);
                    }
                }
            }
        }

        // --- Vehicle 2 Expedition Offline Logic ---
        $vehicle2Status = $planet['vehicle2_status'] ?? 'idle';
        $vehicle2HP = $planet['vehicle2_hp'] ?? 100;
        $vehicle2Level = $planet['vehicle2_level'] ?? 0;

        if (($vehicle2Status === 'exploring' || $vehicle2Status === 'returning') && $vehicle2Level > 0) {
            $startTimeStr = $planet['vehicle2_start_time'] ?? 'now';
            $startTime = new DateTime($startTimeStr);
            $secondsSinceStart = max(0, $now->getTimestamp() - $startTime->getTimestamp());
            $damageSeconds = $secondsSinceStart;

            if ($vehicle2Status === 'returning') {
                $recallTimeStr = $planet['vehicle2_recall_time'] ?? 'now';
                $recallTime = new DateTime($recallTimeStr);
                $secondsToReturn = max(0, $recallTime->getTimestamp() - $startTime->getTimestamp());
                $missionCompleteAfter = $secondsToReturn * 2;
                $damageSeconds = min($secondsSinceStart, $missionCompleteAfter);
            }
            
            $baseDamageRate = 0.1; 
            $acceleration = 0.003;
            // Armor is 2x more effective (level counts double for the bonus)
            $effectiveLevel = 1 + ($vehicle2Level - 1) * 2;
            $armorFactor = pow($effectiveLevel, 1.2);
            $totalDamage = ($damageSeconds * ($baseDamageRate + ($damageSeconds * $acceleration))) / $armorFactor;

            // Auto-Recall Logic (Offline)
            $hasAutoRecall = $planet['research_auto_recall'] ?? 0;
            if ($hasAutoRecall && $vehicle2Status === 'exploring' && $totalDamage >= 10) {
                // Vehicle should have recalled at 90 HP (10 damage)
                $safeSeconds = calculateSafeTime(10, $baseDamageRate, $acceleration, $armorFactor);
                
                $recallTime = clone $startTime;
                $recallTime->modify('+' . round($safeSeconds) . ' seconds');
                
                // Recalculate based on safe recall
                $damageSeconds = $safeSeconds; // Damage stops at recall point
                $totalDamage = 10;
                $vehicle2Status = 'returning';
                
                safePlanetWrite($db, "UPDATE planets SET vehicle2_status = 'returning', vehicle2_recall_time = ?, last_updated = ? WHERE id = ?", [
                    $recallTime->format('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s'),
                    $planet['id']
                ]);
            }

            $currentHP = max(0, 100 - $totalDamage);
            
            if ($currentHP <= 0) {
                $vehicle2Status = 'destroyed';
                $vehicle2HP = 0;
                $vehicle2Level = 0;
                safePlanetWrite($db, "UPDATE planets SET vehicle2_status = 'destroyed', vehicle2_level = 0, vehicle2_hp = 0, last_updated = ? WHERE id = ?", [date('Y-m-d H:i:s'), $planet['id']]);
            } else {
                $vehicle2HP = $currentHP;
                if ($vehicle2Status === 'returning') {
                    $recallTimeStr = $planet['vehicle2_recall_time'] ?? 'now';
                    $recallTime = new DateTime($recallTimeStr);
                    $secondsReturning = max(0, $now->getTimestamp() - $recallTime->getTimestamp());
                    $secondsToReturn = max(0, $recallTime->getTimestamp() - $startTime->getTimestamp());
                    
                    if ($secondsReturning >= $secondsToReturn) {
                        $sensorLvl = $planet['vehicle2_sensor_lvl'] ?? 1;
                        $timeBonus = 1 + ($secondsToReturn * 0.0005);
                        // Sensors are 2x more effective (10% bonus instead of 5%)
                        $crystalRate = 0.2 * (1 + ($sensorLvl - 1) * 0.10) * $timeBonus;
                        $crystalsFound = floor($secondsToReturn * $crystalRate);
                        $crystalAmount += $crystalsFound;
                        $vehicle2Status = 'idle';
                        $vehicle2HP = 100;
                        safePlanetWrite($db, "UPDATE planets SET crystal_amount = ?, vehicle2_status = 'idle', vehicle2_hp = 100, last_updated = ? WHERE id = ?", [$crystalAmount, date('Y-m-d H:i:s'), $planet['id']]);
                    }
                }
            }
        }
        
        // --- Drone Logic ---
        $hasDrone = $planet['has_drone'] ?? 0;
        $droneStorage = $planet['drone_storage'] ?? 0;
        $droneUpgrade1 = $planet['research_drone_upgrade'] ?? 0;
        $droneUpgrade2 = $planet['research_drone_upgrade_2'] ?? 0;
        $droneUpgrade3 = $planet['research_drone_upgrade_3'] ?? 0;

        if ($hasDrone) {
            $multiplier = 1;
            if ($droneUpgrade3) $multiplier = 100; // 25 * 4
            elseif ($droneUpgrade2) $multiplier = 25;
            elseif ($droneUpgrade1) $multiplier = 5;

            $droneProdPerSec = (1 / 300) * $multiplier;
            $droneLimit = 100 * $multiplier;
            
            $droneStorage += ($secondsElapsed * $droneProdPerSec);
            $droneStorage = min($droneLimit, $droneStorage);
        }

        // --- Rocket Workshop Offline Logic ---
        $rocketWorkshopStatus = $planet['rocket_workshop_status'] ?? 'idle';
        $rocketWorkshopReadyAt = $planet['rocket_workshop_ready_at'] ?? null;
        $rocketWorkshop2Status = $planet['rocket_workshop_2_status'] ?? 'idle';
        $rocketWorkshop2ReadyAt = $planet['rocket_workshop_2_ready_at'] ?? null;

        if (($planet['research_rocket_workshop'] ?? 0)) {
            // Slot 1 (Běžná)
            if ($rocketWorkshopStatus === 'producing' && $rocketWorkshopReadyAt) {
                if ($now >= new DateTime($rocketWorkshopReadyAt)) {
                    $rocketWorkshopStatus = 'ready';
                    safePlanetWrite($db, "UPDATE planets SET rocket_workshop_status = 'ready', last_updated = ? WHERE id = ?", [date('Y-m-d H:i:s'), $planet['id']]);
                }
            }
            // Slot 2 (Těžká - unlocked at Lvl 2)
            if (($planet['rocket_workshop_level'] ?? 1) >= 2 && $rocketWorkshop2Status === 'producing' && $rocketWorkshop2ReadyAt) {
                if ($now >= new DateTime($rocketWorkshop2ReadyAt)) {
                    $rocketWorkshop2Status = 'ready';
                    safePlanetWrite($db, "UPDATE planets SET rocket_workshop_2_status = 'ready', last_updated = ? WHERE id = ?", [date('Y-m-d H:i:s'), $planet['id']]);
                }
            }
        }

        // --- PERSISTENCE: Save calculated resources back to DB ---
        $updateSql = "UPDATE planets SET 
            iron_amount = ?, energy_amount = ?, crystal_amount = ?, 
            res_yellow = ?, res_red = ?, res_blue = ?, 
            res_green = ?, res_orange = ?, res_purple = ?,
            res_copper = ?,
            res_tubes = ?,
            drone_storage = ?,
            last_updated = ? 
            WHERE id = ?";
        
        safePlanetWrite($db, $updateSql, [
            $newIron, $newEnergy, $crystalAmount,
            $newResData['yellow']['amount'], $newResData['red']['amount'], $newResData['blue']['amount'],
            $newResData['green']['amount'], $newResData['orange']['amount'], $newResData['purple']['amount'],
            $newCopper,
            $newTubes,
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
            'research_drone_upgrade' => $planet['research_drone_upgrade'],
            'research_drone_upgrade_2' => $planet['research_drone_upgrade_2'],
            'research_drone_upgrade_3' => $planet['research_drone_upgrade_3'] ?? 0,
            'research_warehouse_copper' => $planet['research_warehouse_copper'] ?? 0,
            'research_auto_recall' => $planet['research_auto_recall'] ?? 0,
            'research_advanced_lab' => $planet['research_advanced_lab'] ?? 0,
            'research_rocket_workshop' => $planet['research_rocket_workshop'] ?? 0,
            'research_alien_slot_3' => $planet['research_alien_slot_3'] ?? 0,
            'lab_level' => $labLvl,
            'lab_storage_level' => $labStorageLvl,
            'res_tubes' => $newTubes,
            'tube_production' => $tubeProd,
            'tube_storage_limit' => $tubeLimit,
            'rocket_workshop_level' => $planet['rocket_workshop_level'] ?? 0,
            'rocket_workshop_status' => $rocketWorkshopStatus,
            'rocket_workshop_mode' => $planet['rocket_workshop_mode'] ?? 1,
            'rocket_workshop_started_at' => $planet['rocket_workshop_started_at'] ?? null,
            'rocket_workshop_ready_at' => $rocketWorkshopReadyAt,
            'rocket_workshop_2_status' => $rocketWorkshop2Status,
            'rocket_workshop_2_ready_at' => $rocketWorkshop2ReadyAt,
            'rocket_parts' => $rocketParts,
            'rocket_parts_total' => array_sum($rocketParts),
            'rocket_parts_all_completed' => count($availableRocketParts) === 0,
            'vehicle_level' => $vehicleLevel,
            'vehicle_sensor_lvl' => $planet['vehicle_sensor_lvl'] ?? 1,
            'vehicle_hp' => $vehicleHP,
            'vehicle_status' => $vehicleStatus,
            'vehicle_start_time' => $planet['vehicle_start_time'] ?? null,
            'vehicle_recall_time' => $planet['vehicle_recall_time'] ?? null,
            'vehicle2_level' => $vehicle2Level,
            'vehicle2_sensor_lvl' => $planet['vehicle2_sensor_lvl'] ?? 1,
            'vehicle2_hp' => $vehicle2HP,
            'vehicle2_status' => $vehicle2Status,
            'vehicle2_start_time' => $planet['vehicle2_start_time'] ?? null,
            'vehicle2_recall_time' => $planet['vehicle2_recall_time'] ?? null,
            'last_updated' => $planet['last_updated'],
            'iron_production' => $ironProd,
            'energy_production' => $energyProd,
            'iron_storage_limit' => $ironLimit,
            'copper_production' => $copperProd,
            'copper_storage_limit' => $copperLimit,
            'drone_storage_limit' => $droneUpgrade3 ? 10000 : ($droneUpgrade2 ? 2500 : ($droneUpgrade1 ? 500 : 100)),
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


