<?php

function handlePlanetAction(string $action, int $userId, PDO $db): bool
{
    if ($action === 'get_planet') {
        $planet = getPlanetData($userId, $db);
        echo json_encode($planet);
        return true;
    }

    if ($action === 'upgrade') {
        $type = $_POST['type'] ?? '';
        if (!in_array($type, ALLOWED_UPGRADE_TYPES, true)) {
            echo json_encode(['error' => 'Neplatný typ vylepšení!']);
            return true;
        }

        $planet = getPlanetData($userId, $db);
        if (!$planet) {
            echo json_encode(['error' => 'Planeta nenalezena!']);
            return true;
        }

        $currentLevel = 0;
        if ($type === 'mine') {
            $currentLevel = $planet['mine_level'];
        }
        if ($type === 'solar') {
            $currentLevel = $planet['solar_plant_level'];
        }
        if ($type === 'warehouse') {
            $currentLevel = $planet['warehouse_level'];
        }

        $cost = UPGRADE_COST_MULTIPLIER * $currentLevel;
        if ($planet['iron_amount'] >= $cost) {
            if ($type === 'warehouse' && $currentLevel >= 200) {
                echo json_encode(['error' => 'Sklad nelze dále vylepšovat za železo (max Lvl 200)!']);
                return true;
            }

            $newLevel = $currentLevel + 1;
            $newIron = $planet['iron_amount'] - $cost;
            upgradeBaseBuilding($db, $userId, $type, $newIron, $planet['energy_amount'], $newLevel, date('Y-m-d H:i:s'));

            echo json_encode(['success' => true, 'planet' => getPlanetData($userId, $db)]);
        } else {
            echo json_encode(['error' => 'Nedostatek železa!']);
        }

        return true;
    }

    if ($action === 'upgrade_warehouse_copper_eff') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_warehouse_copper']) {
            echo json_encode(['error' => 'Výzkum není dokončen!']);
            return true;
        }

        $cost = ($planet['warehouse_level'] + 1) * 10;
        if ($planet['res_copper'] >= $cost) {
            upgradeWarehouseCopperEfficiency($db, $userId, $cost, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => "Nedostatek mědi ({$cost} Cu)!"]);
        }

        return true;
    }

    if ($action === 'upgrade_lab') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_advanced_lab']) {
            echo json_encode(['error' => 'Laboratoř není vyzkoumána!']);
            return true;
        }

        $currentLevel = $planet['lab_level'];
        $ironCost = ($currentLevel + 1) * 5000;
        $crystalCost = ($currentLevel + 1) * 100;

        if ($planet['iron_amount'] >= $ironCost && $planet['crystal_amount'] >= $crystalCost) {
            upgradeLab($db, $userId, $ironCost, $crystalCost, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek surovin!']);
        }

        return true;
    }

    if ($action === 'upgrade_lab_storage') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_advanced_lab']) {
            echo json_encode(['error' => 'Laboratoř není vyzkoumána!']);
            return true;
        }

        $currentLevel = $planet['lab_storage_level'];
        $ironCost = ($currentLevel + 1) * 8000;
        $crystalCost = ($currentLevel + 1) * 150;

        if ($planet['iron_amount'] >= $ironCost && $planet['crystal_amount'] >= $crystalCost) {
            upgradeLabStorage($db, $userId, $ironCost, $crystalCost, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek surovin!']);
        }

        return true;
    }

    if ($action === 'upgrade_copper_mine') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_copper']) {
            echo json_encode(['error' => 'Měď není vyzkoumána!']);
            return true;
        }

        $currentLevel = $planet['mine_copper_lvl'];
        $ironCost = ($currentLevel + 1) * 1000;
        $crystalCost = ($currentLevel + 1) * 10;

        if ($planet['iron_amount'] >= $ironCost && $planet['crystal_amount'] >= $crystalCost) {
            upgradeCopperMine($db, $userId, $ironCost, $crystalCost, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek surovin!']);
        }

        return true;
    }

    if ($action === 'upgrade_copper_warehouse') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_copper']) {
            echo json_encode(['error' => 'Měď není vyzkoumána!']);
            return true;
        }

        $currentLevel = $planet['warehouse_copper_lvl'];
        $ironCost = ($currentLevel + 1) * 2000;
        $crystalCost = ($currentLevel + 1) * 20;

        if ($planet['iron_amount'] >= $ironCost && $planet['crystal_amount'] >= $crystalCost) {
            upgradeCopperWarehouse($db, $userId, $ironCost, $crystalCost, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek surovin!']);
        }

        return true;
    }

    if ($action === 'upgrade_alien_mine') {
        $color = $_POST['color'] ?? '';
        if (!in_array($color, ALLOWED_COLORS, true)) {
            echo json_encode(['error' => 'Neplatná barva!']);
            return true;
        }

        $planet = getPlanetData($userId, $db);
        $researched = $planet['researched_colors'] ?? [];
        if (!in_array($color, $researched, true)) {
            echo json_encode(['error' => 'Tato barva není vyzkoumána!']);
            return true;
        }

        $currentLevel = $planet['alien_resources'][$color]['lvl'];
        $ironCost = ($currentLevel + 1) * 500;
        $crystalCost = ($currentLevel + 1) * 50;

        if ($planet['iron_amount'] >= $ironCost && $planet['crystal_amount'] >= $crystalCost) {
            $newIron = $planet['iron_amount'] - $ironCost;
            $newCrystals = $planet['crystal_amount'] - $crystalCost;
            $newLevel = $currentLevel + 1;

            upgradeAlienMine($db, $userId, $color, $newIron, $newCrystals, $newLevel, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek surovin (Fe nebo krystaly)!']);
        }

        return true;
    }

    if ($action === 'upgrade_secret_crystal_mine') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_secret_crystal_mine']) {
            echo json_encode(['error' => 'Výzkum není hotov!']);
            return true;
        }

        $currentLevel = $planet['secret_crystal_mine_level'];
        $cost = SECRET_MINE_UPGRADE_BASE_IRON + ($currentLevel * SECRET_MINE_UPGRADE_STEP_IRON);
        if ($planet['iron_amount'] >= $cost) {
            upgradeSecretCrystalMine($db, $userId, $cost, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => "Nedostatek železa ({$cost})!"]);
        }

        return true;
    }

    return false;
}
