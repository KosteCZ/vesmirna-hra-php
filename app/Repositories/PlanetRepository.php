<?php

function createPlanetForUser(PDO $db, int $userId, string $lastUpdatedAt): void {
    $stmt = $db->prepare("INSERT INTO planets (user_id, iron_amount, energy_amount, last_updated) VALUES (?, 100, 50, ?)");
    $stmt->execute([$userId, $lastUpdatedAt]);
}

function findRawPlanetByUserId(PDO $db, int $userId) {
    $stmt = $db->prepare("SELECT p.*, u.player_name FROM planets p JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function findAllPlayersWithPlanets(PDO $db): array {
    $stmt = $db->query("
        SELECT u.player_name, p.*
        FROM users u
        JOIN planets p ON u.id = p.user_id
        ORDER BY u.player_name ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function executePlanetUpdateByUserId(PDO $db, string $sql, array $params): void {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
}

function upgradeBaseBuilding(PDO $db, int $userId, string $type, float $ironAmount, float $energyAmount, int $newLevel, string $updatedAt): void {
    $columns = [
        'mine' => 'mine_level',
        'solar' => 'solar_plant_level',
        'warehouse' => 'warehouse_level',
    ];

    $column = $columns[$type] ?? null;
    if ($column === null) {
        throw new InvalidArgumentException('Unknown building type: ' . $type);
    }

    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET iron_amount = ?, energy_amount = ?, {$column} = ?, last_updated = ? WHERE user_id = ?",
        [$ironAmount, $energyAmount, $newLevel, $updatedAt, $userId]
    );
}

function upgradeWarehouseCopperEfficiency(PDO $db, int $userId, float $copperCost, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET res_copper = res_copper - ?, warehouse_level = warehouse_level + 5, last_updated = ? WHERE user_id = ?",
        [$copperCost, $updatedAt, $userId]
    );
}

function upgradeLab(PDO $db, int $userId, float $ironCost, float $crystalCost, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET iron_amount = iron_amount - ?, crystal_amount = crystal_amount - ?, lab_level = lab_level + 1, last_updated = ? WHERE user_id = ?",
        [$ironCost, $crystalCost, $updatedAt, $userId]
    );
}

function upgradeLabStorage(PDO $db, int $userId, float $ironCost, float $crystalCost, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET iron_amount = iron_amount - ?, crystal_amount = crystal_amount - ?, lab_storage_level = lab_storage_level + 1, last_updated = ? WHERE user_id = ?",
        [$ironCost, $crystalCost, $updatedAt, $userId]
    );
}

function upgradeCopperMine(PDO $db, int $userId, float $ironCost, float $crystalCost, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET iron_amount = iron_amount - ?, crystal_amount = crystal_amount - ?, mine_copper_lvl = mine_copper_lvl + 1, last_updated = ? WHERE user_id = ?",
        [$ironCost, $crystalCost, $updatedAt, $userId]
    );
}

function upgradeCopperWarehouse(PDO $db, int $userId, float $ironCost, float $crystalCost, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET iron_amount = iron_amount - ?, crystal_amount = crystal_amount - ?, warehouse_copper_lvl = warehouse_copper_lvl + 1, last_updated = ? WHERE user_id = ?",
        [$ironCost, $crystalCost, $updatedAt, $userId]
    );
}

function upgradeAlienMine(PDO $db, int $userId, string $color, float $ironAmount, float $crystalAmount, int $newLevel, string $updatedAt): void {
    $allowedColors = ['yellow', 'red', 'blue', 'green', 'orange', 'purple'];
    if (!in_array($color, $allowedColors, true)) {
        throw new InvalidArgumentException('Unknown alien mine color: ' . $color);
    }

    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET iron_amount = ?, crystal_amount = ?, mine_{$color}_lvl = ?, last_updated = ? WHERE user_id = ?",
        [$ironAmount, $crystalAmount, $newLevel, $updatedAt, $userId]
    );
}

function upgradeSecretCrystalMine(PDO $db, int $userId, float $ironCost, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET iron_amount = iron_amount - ?, secret_crystal_mine_level = secret_crystal_mine_level + 1, last_updated = ? WHERE user_id = ?",
        [$ironCost, $updatedAt, $userId]
    );
}

function buyVehicle(PDO $db, int $userId, float $ironAmount, float $energyAmount, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET iron_amount = ?, energy_amount = ?, vehicle_level = 1, vehicle_hp = 100, vehicle_status = 'idle', last_updated = ? WHERE user_id = ?",
        [$ironAmount, $energyAmount, $updatedAt, $userId]
    );
}

function startVehicleExpedition(PDO $db, int $userId, float $ironAmount, float $energyAmount, string $startedAt, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET iron_amount = ?, energy_amount = ?, vehicle_status = 'exploring', vehicle_start_time = ?, last_updated = ? WHERE user_id = ?",
        [$ironAmount, $energyAmount, $startedAt, $updatedAt, $userId]
    );
}

function recallVehicle(PDO $db, int $userId, float $ironAmount, float $energyAmount, string $recallAt, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET iron_amount = ?, energy_amount = ?, vehicle_status = 'returning', vehicle_recall_time = ?, last_updated = ? WHERE user_id = ?",
        [$ironAmount, $energyAmount, $recallAt, $updatedAt, $userId]
    );
}

function finishVehicleExpedition(PDO $db, int $userId, float $ironAmount, float $energyAmount, float $crystalsFound, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET iron_amount = ?, energy_amount = ?, crystal_amount = crystal_amount + ?, vehicle_status = 'idle', vehicle_hp = 100, last_updated = ? WHERE user_id = ?",
        [$ironAmount, $energyAmount, $crystalsFound, $updatedAt, $userId]
    );
}

function destroyVehicle(PDO $db, int $userId, float $ironAmount, float $energyAmount, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET iron_amount = ?, energy_amount = ?, vehicle_status = 'destroyed', vehicle_level = 0, last_updated = ? WHERE user_id = ?",
        [$ironAmount, $energyAmount, $updatedAt, $userId]
    );
}

function upgradeVehicle(PDO $db, int $userId, float $ironAmount, float $energyAmount, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET iron_amount = ?, energy_amount = ?, vehicle_level = vehicle_level + 1, last_updated = ? WHERE user_id = ?",
        [$ironAmount, $energyAmount, $updatedAt, $userId]
    );
}

function upgradeVehicleSensors(PDO $db, int $userId, float $ironAmount, float $energyAmount, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET iron_amount = ?, energy_amount = ?, vehicle_sensor_lvl = vehicle_sensor_lvl + 1, last_updated = ? WHERE user_id = ?",
        [$ironAmount, $energyAmount, $updatedAt, $userId]
    );
}

function buyVehicle2(PDO $db, int $userId, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET res_copper = res_copper - 500, vehicle2_level = 1, vehicle2_hp = 100, vehicle2_status = 'idle', last_updated = ? WHERE user_id = ?",
        [$updatedAt, $userId]
    );
}

function startVehicle2Expedition(PDO $db, int $userId, string $startedAt, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET vehicle2_status = 'exploring', vehicle2_start_time = ?, last_updated = ? WHERE user_id = ?",
        [$startedAt, $updatedAt, $userId]
    );
}

function recallVehicle2(PDO $db, int $userId, string $recallAt, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET vehicle2_status = 'returning', vehicle2_recall_time = ?, last_updated = ? WHERE user_id = ?",
        [$recallAt, $updatedAt, $userId]
    );
}

function finishVehicle2Expedition(PDO $db, int $userId, float $crystalsFound, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET crystal_amount = crystal_amount + ?, vehicle2_status = 'idle', vehicle2_hp = 100, last_updated = ? WHERE user_id = ?",
        [$crystalsFound, $updatedAt, $userId]
    );
}

function destroyVehicle2(PDO $db, int $userId, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET vehicle2_status = 'destroyed', vehicle2_level = 0, last_updated = ? WHERE user_id = ?",
        [$updatedAt, $userId]
    );
}

function upgradeVehicle2Armor(PDO $db, int $userId, float $copperCost, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET res_copper = res_copper - ?, vehicle2_level = vehicle2_level + 1, last_updated = ? WHERE user_id = ?",
        [$copperCost, $updatedAt, $userId]
    );
}

function upgradeVehicle2Sensors(PDO $db, int $userId, float $copperCost, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET res_copper = res_copper - ?, vehicle2_sensor_lvl = vehicle2_sensor_lvl + 1, last_updated = ? WHERE user_id = ?",
        [$copperCost, $updatedAt, $userId]
    );
}

function buyDrone(PDO $db, int $userId, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET crystal_amount = crystal_amount - 250, has_drone = 1, last_updated = ? WHERE user_id = ?",
        [$updatedAt, $userId]
    );
}

function collectDrone(PDO $db, int $userId, float $amount, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET crystal_amount = crystal_amount + ?, drone_storage = 0, last_updated = ? WHERE user_id = ?",
        [$amount, $updatedAt, $userId]
    );
}

function researchSecretCrystalMine(PDO $db, int $userId, float $tubeCost, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET res_tubes = res_tubes - ?, research_secret_crystal_mine = 1, secret_crystal_mine_level = 1, last_updated = ? WHERE user_id = ?",
        [$tubeCost, $updatedAt, $userId]
    );
}

function researchAlienSlot3(PDO $db, int $userId, float $ironCost, float $copperCost, float $tubeCost, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET iron_amount = iron_amount - ?, res_copper = res_copper - ?, res_tubes = res_tubes - ?, research_alien_slot_3 = 1, last_updated = ? WHERE user_id = ?",
        [$ironCost, $copperCost, $tubeCost, $updatedAt, $userId]
    );
}

function researchColor(PDO $db, int $userId, float $crystalCost, string $researchedColors, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET crystal_amount = crystal_amount - ?, researched_colors = ?, last_updated = ? WHERE user_id = ?",
        [$crystalCost, $researchedColors, $updatedAt, $userId]
    );
}

function researchCopper(PDO $db, int $userId, float $ironCost, float $crystalCost, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET iron_amount = iron_amount - ?, crystal_amount = crystal_amount - ?, research_copper = 1, mine_copper_lvl = 1, warehouse_copper_lvl = 1, last_updated = ? WHERE user_id = ?",
        [$ironCost, $crystalCost, $updatedAt, $userId]
    );
}

function researchDroneUpgrade(PDO $db, int $userId, float $copperCost, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET res_copper = res_copper - ?, research_drone_upgrade = 1, last_updated = ? WHERE user_id = ?",
        [$copperCost, $updatedAt, $userId]
    );
}

function researchDroneUpgrade2(PDO $db, int $userId, float $copperCost, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET res_copper = res_copper - ?, research_drone_upgrade_2 = 1, last_updated = ? WHERE user_id = ?",
        [$copperCost, $updatedAt, $userId]
    );
}

function researchAdvancedLab(PDO $db, int $userId, float $copperCost, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET res_copper = res_copper - ?, research_advanced_lab = 1, lab_level = 1, lab_storage_level = 1, last_updated = ? WHERE user_id = ?",
        [$copperCost, $updatedAt, $userId]
    );
}

function researchWarehouseCopper(PDO $db, int $userId, float $tubeCost, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET res_tubes = res_tubes - ?, research_warehouse_copper = 1, last_updated = ? WHERE user_id = ?",
        [$tubeCost, $updatedAt, $userId]
    );
}

function researchDroneUpgrade3(PDO $db, int $userId, float $tubeCost, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET res_tubes = res_tubes - ?, research_drone_upgrade_3 = 1, last_updated = ? WHERE user_id = ?",
        [$tubeCost, $updatedAt, $userId]
    );
}

function researchAutoRecall(PDO $db, int $userId, float $tubeCost, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET res_tubes = res_tubes - ?, research_auto_recall = 1, last_updated = ? WHERE user_id = ?",
        [$tubeCost, $updatedAt, $userId]
    );
}

function researchRocketWorkshop(PDO $db, int $userId, float $tubeCost, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET res_tubes = res_tubes - ?, research_rocket_workshop = 1, rocket_workshop_level = 1, rocket_workshop_status = 'idle', rocket_workshop_mode = 1, rocket_workshop_started_at = NULL, rocket_workshop_ready_at = NULL, last_updated = ? WHERE user_id = ?",
        [$tubeCost, $updatedAt, $userId]
    );
}

function upgradeRocketWorkshop(PDO $db, int $userId, float $ironCost, string $updatedAt): void {
    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET iron_amount = iron_amount - ?, rocket_workshop_level = 2, last_updated = ? WHERE user_id = ?",
        [$ironCost, $updatedAt, $userId]
    );
}

function startRocketWorkshopProduction(
    PDO $db,
    int $userId,
    float $tubeCost,
    string $statusColumn,
    string $startColumn,
    string $readyColumn,
    string $startedAt,
    string $readyAt,
    string $updatedAt
): void {
    $allowedColumns = [
        'rocket_workshop_status',
        'rocket_workshop_started_at',
        'rocket_workshop_ready_at',
        'rocket_workshop_2_status',
        'rocket_workshop_2_started_at',
        'rocket_workshop_2_ready_at',
    ];

    foreach ([$statusColumn, $startColumn, $readyColumn] as $column) {
        if (!in_array($column, $allowedColumns, true)) {
            throw new InvalidArgumentException('Unknown rocket workshop column: ' . $column);
        }
    }

    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET res_tubes = res_tubes - ?, {$statusColumn} = 'producing', {$startColumn} = ?, {$readyColumn} = ?, last_updated = ? WHERE user_id = ?",
        [$tubeCost, $startedAt, $readyAt, $updatedAt, $userId]
    );
}

function resetRocketWorkshopSlotByPlanetId(PDO $db, int $planetId, string $statusColumn, string $startColumn, string $readyColumn, string $updatedAt): void {
    $allowedColumns = [
        'rocket_workshop_status',
        'rocket_workshop_started_at',
        'rocket_workshop_ready_at',
        'rocket_workshop_2_status',
        'rocket_workshop_2_started_at',
        'rocket_workshop_2_ready_at',
    ];

    foreach ([$statusColumn, $startColumn, $readyColumn] as $column) {
        if (!in_array($column, $allowedColumns, true)) {
            throw new InvalidArgumentException('Unknown rocket workshop column: ' . $column);
        }
    }

    $stmt = $db->prepare("UPDATE planets SET {$statusColumn} = 'idle', {$startColumn} = NULL, {$readyColumn} = NULL, last_updated = ? WHERE id = ?");
    $stmt->execute([$updatedAt, $planetId]);
}

function collectRocketWorkshopProduct(PDO $db, int $userId, string $rocketPartsJson, string $statusColumn, string $startColumn, string $readyColumn, string $updatedAt): void {
    $allowedColumns = [
        'rocket_workshop_status',
        'rocket_workshop_started_at',
        'rocket_workshop_ready_at',
        'rocket_workshop_2_status',
        'rocket_workshop_2_started_at',
        'rocket_workshop_2_ready_at',
    ];

    foreach ([$statusColumn, $startColumn, $readyColumn] as $column) {
        if (!in_array($column, $allowedColumns, true)) {
            throw new InvalidArgumentException('Unknown rocket workshop column: ' . $column);
        }
    }

    executePlanetUpdateByUserId(
        $db,
        "UPDATE planets SET rocket_parts = ?, {$statusColumn} = 'idle', {$startColumn} = NULL, {$readyColumn} = NULL, last_updated = ? WHERE user_id = ?",
        [$rocketPartsJson, $updatedAt, $userId]
    );
}
