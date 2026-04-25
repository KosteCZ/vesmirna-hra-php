<?php
// db.php - SQLite connection and initialization

// Disable error reporting to prevent warnings from breaking JSON output
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set timezone to UTC
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
    'research_secret_crystal_mine' => "INTEGER DEFAULT 0",
    'secret_crystal_mine_level' => "INTEGER DEFAULT 0",
    'rocket_parts' => "TEXT DEFAULT ''"
];

$res = $db->query("PRAGMA table_info(planets)");
$existingColumns = [];
if ($res) {
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $row['name'];
    }
}

// Migration: Column renames and additions
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
        if (isReadonlyDatabaseError($e)) return false;
        throw $e;
    }
}

function calculateSafeTime($targetDamage, $baseRate, $acceleration, $armorFactor) {
    $inner = pow($baseRate, 2) + 4 * $acceleration * $targetDamage * $armorFactor;
    if ($inner < 0) return 0;
    return (-$baseRate + sqrt($inner)) / (2 * $acceleration);
}

function getRocketPartDefinitions(): array {
    return [
        'rocket_tip' => "Špička rakety",
        'rocket_body' => 'Trup rakety',
        'fuel_tank' => "Palivové nádrže",
        'jet_engine' => "Tryskový motor",
        'satellite' => 'Satelit',
        'solar_panel' => "Solární panel",
        'seat' => 'Sedadlo',
        'fuel_canister' => "Kanystr s palivem",
        'electronics' => "Elektronické zařízení",
        'tools' => "Nářadí"
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
    if (!is_string($rawInventory) || $rawInventory === '') return $inventory;
    $decoded = json_decode($rawInventory, true);
    if (!is_array($decoded)) return $inventory;
    foreach ($inventory as $key => $defaultValue) {
        $value = $decoded[$key] ?? $defaultValue;
        $inventory[$key] = max(0, min(10, (int)$value));
    }
    return $inventory;
}

function getPlanetData($userId, $db) {
    try {
        $stmt = $db->prepare("SELECT p.*, u.player_name FROM planets p JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
        $stmt->execute([$userId]);
        $planet = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$planet) return null;

        $now = new DateTime();
        $lastUpdate = new DateTime($planet['last_updated'] ?? 'now');
        $secondsElapsed = max(0, $now->getTimestamp() - $lastUpdate->getTimestamp());

        $ironProd = ($planet['mine_level'] ?? 1) * 1;
        $energyProd = ($planet['solar_plant_level'] ?? 1) * 2;
        $ironLimit = ($planet['warehouse_level'] ?? 1) * 1000;

        $colors = ['yellow', 'red', 'blue', 'green', 'orange', 'purple'];
        $newResData = [];
        $totalExtraEnergyNeeded = 0;
        foreach ($colors as $color) {
            $lvl = $planet["mine_{$color}_lvl"] ?? 0;
            $prod = $lvl * 0.02;
            $newResData[$color] = ['lvl' => $lvl, 'prod' => $prod, 'amount' => $planet["res_{$color}"] ?? 0];
            $totalExtraEnergyNeeded += ($lvl * 0.3);
        }

        $copperLvl = $planet['mine_copper_lvl'] ?? 0;
        $copperProd = $copperLvl * 0.1;
        $copperLimit = ($planet['warehouse_copper_lvl'] ?? 0) * 1000 + 500;
        $totalExtraEnergyNeeded += ($copperLvl * 0.5);

        $labLvl = $planet['lab_level'] ?? 0;
        $labStorageLvl = $planet['lab_storage_level'] ?? 0;
        $tubeProd = $labLvl * 0.05;
        $tubeLimit = ($labStorageLvl > 0) ? ($labStorageLvl * 500) : 100;
        if (($planet['research_advanced_lab'] ?? 0)) $totalExtraEnergyNeeded += ($labLvl * 1.5);

        // --- Secret Crystal Mine Production ---
        $secretMineLvl = $planet['secret_crystal_mine_level'] ?? 0;
        $secretMineProd = 0;
        $totalDiscovered = 0;
        $stmtCount = $db->query("SELECT COUNT(*) FROM planets WHERE research_secret_crystal_mine = 1");
        if ($stmtCount) $totalDiscovered = (int)$stmtCount->fetchColumn();
        if ($secretMineLvl > 0) {
            // Base rate: 30 * 2 ^ discovered (Results in 60/h for 1st player, 120/h for 2nd, etc.)
            $baseRatePerHour = 30 * pow(2, $totalDiscovered);
            $secretMineProd = ($baseRatePerHour / 3600) * $secretMineLvl;
            $totalExtraEnergyNeeded += ($secretMineLvl * 10);
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
            if ($secondsElapsed > 0) $productionFactor = ($secondsWithEnergy + (($secondsElapsed - $secondsWithEnergy) * 0.1)) / $secondsElapsed;
            $newEnergy = $currentEnergy - ($secondsWithEnergy * $energyDiff) + (max(0, $secondsElapsed - $secondsWithEnergy) * $energyProd);
        } else {
            $newEnergy = $currentEnergy + ($secondsElapsed * ($energyProd - $energyNeeded));
        }

        $newIron = min($ironLimit, $currentIron + ($secondsElapsed * $ironProd * $productionFactor));
        $newEnergy = max(0, $newEnergy);
        $newCopper = min($copperLimit, $currentCopper + ($secondsElapsed * $copperProd * $productionFactor));
        $newTubes = min($tubeLimit, $currentTubes + ($secondsElapsed * $tubeProd * $productionFactor));
        $newCrystals = ($planet['crystal_amount'] ?? 0) + ($secondsElapsed * $secretMineProd * $productionFactor);

        foreach ($colors as $color) {
            $newResData[$color]['amount'] += ($secondsElapsed * $newResData[$color]['prod'] * $productionFactor);
        }

        // Expedition & Rocket Workshop Logic
        $vehicleStatus = $planet['vehicle_status'] ?? 'idle';
        $vehicleHP = $planet['vehicle_hp'] ?? 100;
        $vehicleLevel = $planet['vehicle_level'] ?? 0;
        $crystalAmount = $newCrystals;

        if (($vehicleStatus === 'exploring' || $vehicleStatus === 'returning') && $vehicleLevel > 0) {
            $startTime = new DateTime($planet['vehicle_start_time'] ?? 'now');
            $secondsSinceStart = max(0, $now->getTimestamp() - $startTime->getTimestamp());
            $damageSeconds = $secondsSinceStart;
            if ($vehicleStatus === 'returning') {
                $recallTime = new DateTime($planet['vehicle_recall_time'] ?? 'now');
                $secondsToReturn = max(0, $recallTime->getTimestamp() - $startTime->getTimestamp());
                $damageSeconds = min($secondsSinceStart, $secondsToReturn * 2);
            }
            $baseDamageRate = 0.1; $acceleration = 0.003; $armorFactor = pow($vehicleLevel, 1.2);
            $totalDamage = ($damageSeconds * ($baseDamageRate + ($damageSeconds * $acceleration))) / $armorFactor;
            if (($planet['research_auto_recall'] ?? 0) && $vehicleStatus === 'exploring' && $totalDamage >= 10) {
                $safeSecs = calculateSafeTime(10, $baseDamageRate, $acceleration, $armorFactor);
                $recallTime = clone $startTime; $recallTime->modify('+' . round($safeSecs) . ' seconds');
                $damageSeconds = $safeSecs; $totalDamage = 10; $vehicleStatus = 'returning';
                safePlanetWrite($db, "UPDATE planets SET vehicle_status = 'returning', vehicle_recall_time = ?, last_updated = ? WHERE id = ?", [$recallTime->format('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $planet['id']]);
            }
            $currentHP = max(0, 100 - $totalDamage);
            if ($currentHP <= 0) {
                $vehicleStatus = 'destroyed'; $vehicleHP = 0; $vehicleLevel = 0;
                safePlanetWrite($db, "UPDATE planets SET vehicle_status = 'destroyed', vehicle_level = 0, vehicle_hp = 0, last_updated = ? WHERE id = ?", [date('Y-m-d H:i:s'), $planet['id']]);
            } else {
                $vehicleHP = $currentHP;
                if ($vehicleStatus === 'returning') {
                    $recallTime = new DateTime($planet['vehicle_recall_time'] ?? 'now');
                    if (($now->getTimestamp() - $recallTime->getTimestamp()) >= ($recallTime->getTimestamp() - $startTime->getTimestamp())) {
                        $sensorLvl = $planet['vehicle_sensor_lvl'] ?? 1;
                        $timeBonus = 1 + (($recallTime->getTimestamp() - $startTime->getTimestamp()) * 0.0005);
                        $crystalAmount += floor(($recallTime->getTimestamp() - $startTime->getTimestamp()) * (0.1 * (1 + ($sensorLvl - 1) * 0.05) * $timeBonus));
                        $vehicleStatus = 'idle'; $vehicleHP = 100;
                        safePlanetWrite($db, "UPDATE planets SET crystal_amount = ?, vehicle_status = 'idle', vehicle_hp = 100, last_updated = ? WHERE id = ?", [$crystalAmount, date('Y-m-d H:i:s'), $planet['id']]);
                    }
                }
            }
        }

        // Vehicle 2 Logic
        $vehicle2Status = $planet['vehicle2_status'] ?? 'idle';
        $vehicle2HP = $planet['vehicle2_hp'] ?? 100;
        $vehicle2Level = $planet['vehicle2_level'] ?? 0;
        if (($vehicle2Status === 'exploring' || $vehicle2Status === 'returning') && $vehicle2Level > 0) {
            $startTime = new DateTime($planet['vehicle2_start_time'] ?? 'now');
            $secondsSinceStart = max(0, $now->getTimestamp() - $startTime->getTimestamp());
            $damageSeconds = $secondsSinceStart;
            if ($vehicle2Status === 'returning') {
                $recallTime = new DateTime($planet['vehicle2_recall_time'] ?? 'now');
                $secondsToReturn = max(0, $recallTime->getTimestamp() - $startTime->getTimestamp());
                $damageSeconds = min($secondsSinceStart, $secondsToReturn * 2);
            }
            $baseDamageRate = 0.1; $acceleration = 0.003; $effLvl = 1 + ($vehicle2Level - 1) * 2; $armorFactor = pow($effLvl, 1.2);
            $totalDamage = ($damageSeconds * ($baseDamageRate + ($damageSeconds * $acceleration))) / $armorFactor;
            if (($planet['research_auto_recall'] ?? 0) && $vehicle2Status === 'exploring' && $totalDamage >= 10) {
                $safeSecs = calculateSafeTime(10, $baseDamageRate, $acceleration, $armorFactor);
                $recallTime = clone $startTime; $recallTime->modify('+' . round($safeSecs) . ' seconds');
                $damageSeconds = $safeSecs; $totalDamage = 10; $vehicle2Status = 'returning';
                safePlanetWrite($db, "UPDATE planets SET vehicle2_status = 'returning', vehicle2_recall_time = ?, last_updated = ? WHERE id = ?", [$recallTime->format('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $planet['id']]);
            }
            $currentHP = max(0, 100 - $totalDamage);
            if ($currentHP <= 0) {
                $vehicle2Status = 'destroyed'; $vehicle2HP = 0; $vehicle2Level = 0;
                safePlanetWrite($db, "UPDATE planets SET vehicle2_status = 'destroyed', vehicle2_level = 0, vehicle2_hp = 0, last_updated = ? WHERE id = ?", [date('Y-m-d H:i:s'), $planet['id']]);
            } else {
                $vehicle2HP = $currentHP;
                if ($vehicle2Status === 'returning') {
                    $recallTime = new DateTime($planet['vehicle2_recall_time'] ?? 'now');
                    if (($now->getTimestamp() - $recallTime->getTimestamp()) >= ($recallTime->getTimestamp() - $startTime->getTimestamp())) {
                        $sensorLvl = $planet['vehicle2_sensor_lvl'] ?? 1;
                        $timeBonus = 1 + (($recallTime->getTimestamp() - $startTime->getTimestamp()) * 0.0005);
                        $crystalAmount += floor(($recallTime->getTimestamp() - $startTime->getTimestamp()) * (0.2 * (1 + ($sensorLvl - 1) * 0.10) * $timeBonus));
                        $vehicle2Status = 'idle'; $vehicle2HP = 100;
                        safePlanetWrite($db, "UPDATE planets SET crystal_amount = ?, vehicle2_status = 'idle', vehicle2_hp = 100, last_updated = ? WHERE id = ?", [$crystalAmount, date('Y-m-d H:i:s'), $planet['id']]);
                    }
                }
            }
        }

        // Drone storage
        $droneStorage = $planet['drone_storage'] ?? 0;
        if ($planet['has_drone'] ?? 0) {
            $mult = ($planet['research_drone_upgrade_3'] ?? 0) ? 100 : (($planet['research_drone_upgrade_2'] ?? 0) ? 25 : (($planet['research_drone_upgrade'] ?? 0) ? 5 : 1));
            $droneStorage = min(100 * $mult, $droneStorage + ($secondsElapsed * (1/300) * $mult));
        }

        // Rocket Workshop
        $rwStat1 = $planet['rocket_workshop_status'] ?? 'idle';
        $rwStat2 = $planet['rocket_workshop_2_status'] ?? 'idle';
        if (($planet['research_rocket_workshop'] ?? 0)) {
            if ($rwStat1 === 'producing' && $now >= new DateTime($planet['rocket_workshop_ready_at'])) { $rwStat1 = 'ready'; safePlanetWrite($db, "UPDATE planets SET rocket_workshop_status = 'ready' WHERE id = ?", [$planet['id']]); }
            if (($planet['rocket_workshop_level'] ?? 1) >= 2 && $rwStat2 === 'producing' && $now >= new DateTime($planet['rocket_workshop_2_ready_at'])) { $rwStat2 = 'ready'; safePlanetWrite($db, "UPDATE planets SET rocket_workshop_2_status = 'ready' WHERE id = ?", [$planet['id']]); }
        }

        // Persistence
        safePlanetWrite($db, "UPDATE planets SET iron_amount = ?, energy_amount = ?, crystal_amount = ?, res_yellow = ?, res_red = ?, res_blue = ?, res_green = ?, res_orange = ?, res_purple = ?, res_copper = ?, res_tubes = ?, drone_storage = ?, last_updated = ? WHERE id = ?", [
            $newIron, $newEnergy, $crystalAmount, $newResData['yellow']['amount'], $newResData['red']['amount'], $newResData['blue']['amount'], $newResData['green']['amount'], $newResData['orange']['amount'], $newResData['purple']['amount'], $newCopper, $newTubes, $droneStorage, date('Y-m-d H:i:s'), $planet['id']
        ]);

        $rocketParts = normalizeRocketPartsInventory($planet['rocket_parts'] ?? '');
        $partsLeft = 0; foreach ($rocketParts as $c) if ($c < 10) $partsLeft++;

        $researchedStr = $planet['researched_colors'] ?? '';
        $researchedArr = $researchedStr ? explode(',', $researchedStr) : [];

        return [
            'id' => $planet['id'], 'user_id' => $planet['user_id'], 'player_name' => $planet['player_name'],
            'iron_amount' => $newIron, 'energy_amount' => $newEnergy, 'crystal_amount' => $crystalAmount, 'res_copper' => $newCopper,
            'mine_level' => $planet['mine_level'], 'solar_plant_level' => $planet['solar_plant_level'], 'warehouse_level' => $planet['warehouse_level'],
            'mine_copper_lvl' => $planet['mine_copper_lvl'], 'warehouse_copper_lvl' => $planet['warehouse_copper_lvl'],
            'research_copper' => $planet['research_copper'], 'research_drone_upgrade' => $planet['research_drone_upgrade'], 'research_drone_upgrade_2' => $planet['research_drone_upgrade_2'],
            'research_drone_upgrade_3' => $planet['research_drone_upgrade_3'] ?? 0, 'research_warehouse_copper' => $planet['research_warehouse_copper'] ?? 0,
            'research_auto_recall' => $planet['research_auto_recall'] ?? 0, 'research_advanced_lab' => $planet['research_advanced_lab'] ?? 0,
            'research_rocket_workshop' => $planet['research_rocket_workshop'] ?? 0, 'research_alien_slot_3' => $planet['research_alien_slot_3'] ?? 0,
            'research_secret_crystal_mine' => $planet['research_secret_crystal_mine'] ?? 0, 'secret_crystal_mine_level' => $planet['secret_crystal_mine_level'] ?? 0,
            'secret_mine_production' => $secretMineProd, 'secret_mine_discovered_count' => $totalDiscovered,
            'lab_level' => $labLvl, 'lab_storage_level' => $labStorageLvl, 'res_tubes' => $newTubes, 'tube_production' => $tubeProd, 'tube_storage_limit' => $tubeLimit,
            'rocket_workshop_level' => $planet['rocket_workshop_level'] ?? 0, 'rocket_workshop_status' => $rwStat1, 'rocket_workshop_mode' => $planet['rocket_workshop_mode'] ?? 1,
            'rocket_workshop_ready_at' => $planet['rocket_workshop_ready_at'] ?? null, 'rocket_workshop_2_status' => $rwStat2, 'rocket_workshop_2_ready_at' => $planet['rocket_workshop_2_ready_at'] ?? null,
            'rocket_parts' => $rocketParts, 'rocket_parts_total' => array_sum($rocketParts), 'rocket_parts_all_completed' => $partsLeft === 0,
            'vehicle_level' => $vehicleLevel, 'vehicle_hp' => $vehicleHP, 'vehicle_status' => $vehicleStatus,
            'vehicle2_level' => $vehicle2Level, 'vehicle2_hp' => $vehicle2HP, 'vehicle2_status' => $vehicle2Status,
            'last_updated' => date('Y-m-d H:i:s'), 'iron_production' => $ironProd, 'energy_production' => $energyProd, 'iron_storage_limit' => $ironLimit,
            'copper_production' => $copperProd, 'copper_storage_limit' => $copperLimit, 'drone_storage_limit' => ($mult ?? 1) * 100,
            'researched_colors' => $researchedArr,
            'alien_resources' => $newResData, 'has_drone' => $planet['has_drone'] ?? 0, 'drone_storage' => $droneStorage
        ];
    } catch (Exception $e) {
        return isset($planet) ? $planet : null;
    }
}
