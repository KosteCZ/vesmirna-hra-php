<?php

function handleExpeditionAction(string $action, int $userId, PDO $db): bool
{
    if ($action === 'buy_vehicle') {
        $planet = getPlanetData($userId, $db);
        if ($planet['vehicle_level'] > 0 && $planet['vehicle_status'] !== 'destroyed') {
            echo json_encode(['error' => 'Vozidlo už máš!']);
            return true;
        }

        if ($planet['iron_amount'] >= 500) {
            $newIron = $planet['iron_amount'] - 500;
            buyVehicle($db, $userId, $newIron, $planet['energy_amount'], date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek železa!']);
        }

        return true;
    }

    if ($action === 'start_expedition') {
        $planet = getPlanetData($userId, $db);
        if (($planet['vehicle_level'] ?? 0) <= 0) {
            echo json_encode(['error' => 'Nejdřív musíš koupit vozidlo!']);
            return true;
        }
        if (($planet['vehicle_status'] ?? 'idle') !== 'idle') {
            echo json_encode(['error' => 'Vozidlo už je na misi nebo mimo provoz!']);
            return true;
        }

        $now = date('Y-m-d H:i:s');
        startVehicleExpedition($db, $userId, $planet['iron_amount'], $planet['energy_amount'], $now, $now);
        echo json_encode(['success' => true]);
        return true;
    }

    if ($action === 'recall_vehicle') {
        $planet = getPlanetData($userId, $db);
        if (($planet['vehicle_status'] ?? '') !== 'exploring') {
            echo json_encode(['error' => 'Vozidlo zrovna není na průzkumu!']);
            return true;
        }

        $now = date('Y-m-d H:i:s');
        recallVehicle($db, $userId, $planet['iron_amount'], $planet['energy_amount'], $now, $now);
        echo json_encode(['success' => true]);
        return true;
    }

    if ($action === 'finish_expedition') {
        $planet = getPlanetData($userId, $db);
        if (($planet['vehicle_status'] ?? '') !== 'returning' || empty($planet['vehicle_start_time']) || empty($planet['vehicle_recall_time'])) {
            echo json_encode(['error' => 'Expedici nelze dokončit v aktuálním stavu!']);
            return true;
        }

        $startTime = new DateTime($planet['vehicle_start_time']);
        $recallTime = new DateTime($planet['vehicle_recall_time']);
        $secondsOut = $recallTime->getTimestamp() - $startTime->getTimestamp();
        $sensorLvl = $planet['vehicle_sensor_lvl'] ?? 1;
        $timeBonus = 1 + ($secondsOut * 0.0005);
        $crystalRate = 0.1 * (1 + ($sensorLvl - 1) * 0.05) * $timeBonus;
        $crystalsFound = floor($secondsOut * $crystalRate);

        finishVehicleExpedition($db, $userId, $planet['iron_amount'], $planet['energy_amount'], $crystalsFound, date('Y-m-d H:i:s'));
        echo json_encode(['success' => true]);
        return true;
    }

    if ($action === 'destroy_vehicle') {
        $planet = getPlanetData($userId, $db);
        destroyVehicle($db, $userId, $planet['iron_amount'], $planet['energy_amount'], date('Y-m-d H:i:s'));
        echo json_encode(['success' => true]);
        return true;
    }

    if ($action === 'upgrade_vehicle') {
        $planet = getPlanetData($userId, $db);
        $cost = ($planet['vehicle_level'] + 1) * 500;
        if ($planet['iron_amount'] >= $cost) {
            $newIron = $planet['iron_amount'] - $cost;
            upgradeVehicle($db, $userId, $newIron, $planet['energy_amount'], date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek železa!']);
        }

        return true;
    }

    if ($action === 'upgrade_vehicle_sensors') {
        $planet = getPlanetData($userId, $db);
        $cost = $planet['vehicle_sensor_lvl'] * 1000;
        if ($planet['iron_amount'] >= $cost) {
            $newIron = $planet['iron_amount'] - $cost;
            upgradeVehicleSensors($db, $userId, $newIron, $planet['energy_amount'], date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => "Nedostatek železa ({$cost})!"]);
        }

        return true;
    }

    if ($action === 'buy_vehicle2') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['research_copper']) {
            echo json_encode(['error' => 'Měď není vyzkoumána!']);
            return true;
        }
        if ($planet['vehicle2_level'] > 0 && $planet['vehicle2_status'] !== 'destroyed') {
            echo json_encode(['error' => 'Druhé vozidlo už máš!']);
            return true;
        }
        if ($planet['res_copper'] >= 500) {
            buyVehicle2($db, $userId, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek mědi (500 Cu)!']);
        }

        return true;
    }

    if ($action === 'start_expedition2') {
        $planet = getPlanetData($userId, $db);
        if (($planet['vehicle2_level'] ?? 0) <= 0) {
            echo json_encode(['error' => 'Nejdřív musíš koupit druhé vozidlo!']);
            return true;
        }
        if (($planet['vehicle2_status'] ?? 'idle') !== 'idle') {
            echo json_encode(['error' => 'Druhé vozidlo už je na misi nebo mimo provoz!']);
            return true;
        }
        $now = date('Y-m-d H:i:s');
        startVehicle2Expedition($db, $userId, $now, $now);
        echo json_encode(['success' => true]);
        return true;
    }

    if ($action === 'recall_vehicle2') {
        $planet = getPlanetData($userId, $db);
        if (($planet['vehicle2_status'] ?? '') !== 'exploring') {
            echo json_encode(['error' => 'Druhé vozidlo zrovna není na průzkumu!']);
            return true;
        }
        $now = date('Y-m-d H:i:s');
        recallVehicle2($db, $userId, $now, $now);
        echo json_encode(['success' => true]);
        return true;
    }

    if ($action === 'finish_expedition2') {
        $planet = getPlanetData($userId, $db);
        if (($planet['vehicle2_status'] ?? '') !== 'returning' || empty($planet['vehicle2_start_time']) || empty($planet['vehicle2_recall_time'])) {
            echo json_encode(['error' => 'Expedici druhého vozidla nelze dokončit v aktuálním stavu!']);
            return true;
        }
        $startTime = new DateTime($planet['vehicle2_start_time']);
        $recallTime = new DateTime($planet['vehicle2_recall_time']);
        $secondsOut = $recallTime->getTimestamp() - $startTime->getTimestamp();
        $sensorLvl = $planet['vehicle2_sensor_lvl'] ?? 1;
        $timeBonus = 1 + ($secondsOut * 0.0005);
        $crystalRate = 0.2 * (1 + ($sensorLvl - 1) * 0.10) * $timeBonus;
        $crystalsFound = floor($secondsOut * $crystalRate);
        finishVehicle2Expedition($db, $userId, $crystalsFound, date('Y-m-d H:i:s'));
        echo json_encode(['success' => true]);
        return true;
    }

    if ($action === 'destroy_vehicle2') {
        $planet = getPlanetData($userId, $db);
        destroyVehicle2($db, $userId, date('Y-m-d H:i:s'));
        echo json_encode(['success' => true]);
        return true;
    }

    if ($action === 'upgrade_vehicle2_armor') {
        $planet = getPlanetData($userId, $db);
        $cost = ($planet['vehicle2_level'] + 1) * 100;
        if ($planet['res_copper'] >= $cost) {
            upgradeVehicle2Armor($db, $userId, $cost, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => "Nedostatek mědi ({$cost} Cu)!"]);
        }

        return true;
    }

    if ($action === 'upgrade_vehicle2_sensors') {
        $planet = getPlanetData($userId, $db);
        $cost = $planet['vehicle2_sensor_lvl'] * 150;
        if ($planet['res_copper'] >= $cost) {
            upgradeVehicle2Sensors($db, $userId, $cost, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => "Nedostatek mědi ({$cost} Cu)!"]);
        }

        return true;
    }

    if ($action === 'buy_drone') {
        $planet = getPlanetData($userId, $db);
        if ($planet['has_drone']) {
            echo json_encode(['error' => 'Drona již máš!']);
            return true;
        }

        if ($planet['crystal_amount'] >= 250) {
            buyDrone($db, $userId, date('Y-m-d H:i:s'));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nedostatek krystalů (250)!']);
        }

        return true;
    }

    if ($action === 'collect_drone') {
        $planet = getPlanetData($userId, $db);
        if (!$planet['has_drone']) {
            echo json_encode(['error' => 'Nemáš drona!']);
            return true;
        }

        $amount = $planet['drone_storage'];
        collectDrone($db, $userId, $amount, date('Y-m-d H:i:s'));
        echo json_encode(['success' => true]);
        return true;
    }

    return false;
}
