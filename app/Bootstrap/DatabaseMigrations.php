<?php

function initializeDatabase(PDO $db): void {
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

    $db->exec("CREATE TABLE IF NOT EXISTS global_settings (
        key TEXT PRIMARY KEY,
        value TEXT
    )");

    $db->exec("INSERT OR IGNORE INTO global_settings (key, value) VALUES ('game_state', 'COLONIZATION')");

    ensureUserColumns($db);
    ensurePlanetColumns($db);
}

function ensureUserColumns(PDO $db): void {
    $resUsers = $db->query("PRAGMA table_info(users)");
    $existingUserColumns = [];
    if ($resUsers) {
        while ($row = $resUsers->fetch(PDO::FETCH_ASSOC)) {
            $existingUserColumns[] = $row['name'];
        }
    }

    if (!in_array('last_login', $existingUserColumns, true)) {
        $db->exec("ALTER TABLE users ADD COLUMN last_login DATETIME");
    }
}

function ensurePlanetColumns(PDO $db): void {
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

    $renames = [
        'ship_level' => 'vehicle_level',
        'ship_hp' => 'vehicle_hp',
        'ship_status' => 'vehicle_status',
        'ship_start_time' => 'vehicle_start_time',
        'ship_recall_time' => 'vehicle_recall_time'
    ];

    foreach ($renames as $old => $new) {
        if (in_array($old, $existingColumns, true) && !in_array($new, $existingColumns, true)) {
            try {
                $db->exec("ALTER TABLE planets RENAME COLUMN $old TO $new");
                $existingColumns[] = $new;
            } catch (Exception $e) {
            }
        }
    }

    foreach ($columnsToAdd as $col => $definition) {
        if (!in_array($col, $existingColumns, true)) {
            try {
                $db->exec("ALTER TABLE planets ADD COLUMN $col $definition");
            } catch (Exception $e) {
            }
        }
    }
}
