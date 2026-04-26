<?php

final class PlanetSimulator
{
    public static function simulate(PDO $db, array $planet): array
    {
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
            $newResData[$color] = [
                'lvl' => $lvl,
                'prod' => $prod,
                'amount' => $planet["res_{$color}"] ?? 0,
            ];
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
        if (($planet['research_advanced_lab'] ?? 0)) {
            $totalExtraEnergyNeeded += ($labLvl * 1.5);
        }

        $secretMineLvl = $planet['secret_crystal_mine_level'] ?? 0;
        $secretMineProd = 0;
        $totalDiscovered = 0;
        $stmtCount = $db->query("SELECT COUNT(*) FROM planets WHERE research_secret_crystal_mine = 1");
        if ($stmtCount) {
            $totalDiscovered = (int) $stmtCount->fetchColumn();
        }
        if ($secretMineLvl > 0) {
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
        $newCrystals = ($planet['crystal_amount'] ?? 0) + ($secondsElapsed * $secretMineProd * $productionFactor);

        foreach ($colors as $color) {
            $newResData[$color]['amount'] += ($secondsElapsed * $newResData[$color]['prod'] * $productionFactor);
        }

        $vehicleState = self::simulateVehicleOne($db, $planet, $now, $newCrystals);
        $vehicleStatus = $vehicleState['status'];
        $vehicleHP = $vehicleState['hp'];
        $vehicleLevel = $vehicleState['level'];
        $crystalAmount = $vehicleState['crystal_amount'];

        $vehicle2State = self::simulateVehicleTwo($db, $planet, $now, $crystalAmount);
        $vehicle2Status = $vehicle2State['status'];
        $vehicle2HP = $vehicle2State['hp'];
        $vehicle2Level = $vehicle2State['level'];
        $crystalAmount = $vehicle2State['crystal_amount'];

        $droneStorage = $planet['drone_storage'] ?? 0;
        $droneMultiplier = 1;
        if ($planet['has_drone'] ?? 0) {
            $droneMultiplier = ($planet['research_drone_upgrade_3'] ?? 0) ? 100 : (($planet['research_drone_upgrade_2'] ?? 0) ? 25 : (($planet['research_drone_upgrade'] ?? 0) ? 5 : 1));
            $droneStorage = min(100 * $droneMultiplier, $droneStorage + ($secondsElapsed * (1 / 300) * $droneMultiplier));
        }

        $rwStat1 = $planet['rocket_workshop_status'] ?? 'idle';
        $rwStat2 = $planet['rocket_workshop_2_status'] ?? 'idle';
        if (($planet['research_rocket_workshop'] ?? 0)) {
            if ($rwStat1 === 'producing' && $now >= new DateTime($planet['rocket_workshop_ready_at'])) {
                $rwStat1 = 'ready';
                safePlanetWrite($db, "UPDATE planets SET rocket_workshop_status = 'ready' WHERE id = ?", [$planet['id']]);
            }
            if (($planet['rocket_workshop_level'] ?? 1) >= 2 && $rwStat2 === 'producing' && $now >= new DateTime($planet['rocket_workshop_2_ready_at'])) {
                $rwStat2 = 'ready';
                safePlanetWrite($db, "UPDATE planets SET rocket_workshop_2_status = 'ready' WHERE id = ?", [$planet['id']]);
            }
        }

        safePlanetWrite($db, "UPDATE planets SET iron_amount = ?, energy_amount = ?, crystal_amount = ?, res_yellow = ?, res_red = ?, res_blue = ?, res_green = ?, res_orange = ?, res_purple = ?, res_copper = ?, res_tubes = ?, drone_storage = ?, last_updated = ? WHERE id = ?", [
            $newIron,
            $newEnergy,
            $crystalAmount,
            $newResData['yellow']['amount'],
            $newResData['red']['amount'],
            $newResData['blue']['amount'],
            $newResData['green']['amount'],
            $newResData['orange']['amount'],
            $newResData['purple']['amount'],
            $newCopper,
            $newTubes,
            $droneStorage,
            date('Y-m-d H:i:s'),
            $planet['id'],
        ]);

        $rocketParts = normalizeRocketPartsInventory($planet['rocket_parts'] ?? '');
        $partsLeft = 0;
        foreach ($rocketParts as $count) {
            if ($count < 10) {
                $partsLeft++;
            }
        }

        $researchedStr = $planet['researched_colors'] ?? '';
        $researchedArr = $researchedStr ? explode(',', $researchedStr) : [];

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
            'research_secret_crystal_mine' => $planet['research_secret_crystal_mine'] ?? 0,
            'secret_crystal_mine_level' => $planet['secret_crystal_mine_level'] ?? 0,
            'secret_mine_production' => $secretMineProd,
            'secret_mine_discovered_count' => $totalDiscovered,
            'lab_level' => $labLvl,
            'lab_storage_level' => $labStorageLvl,
            'res_tubes' => $newTubes,
            'tube_production' => $tubeProd,
            'tube_storage_limit' => $tubeLimit,
            'rocket_workshop_level' => $planet['rocket_workshop_level'] ?? 0,
            'rocket_workshop_status' => $rwStat1,
            'rocket_workshop_mode' => $planet['rocket_workshop_mode'] ?? 1,
            'rocket_workshop_ready_at' => $planet['rocket_workshop_ready_at'] ?? null,
            'rocket_workshop_2_status' => $rwStat2,
            'rocket_workshop_2_ready_at' => $planet['rocket_workshop_2_ready_at'] ?? null,
            'rocket_parts' => $rocketParts,
            'rocket_parts_total' => array_sum($rocketParts),
            'rocket_parts_all_completed' => $partsLeft === 0,
            'vehicle_level' => $vehicleLevel,
            'vehicle_hp' => $vehicleHP,
            'vehicle_status' => $vehicleStatus,
            'vehicle_start_time' => $planet['vehicle_start_time'] ?? null,
            'vehicle_recall_time' => $planet['vehicle_recall_time'] ?? null,
            'vehicle_sensor_lvl' => $planet['vehicle_sensor_lvl'] ?? 1,
            'vehicle2_level' => $vehicle2Level,
            'vehicle2_hp' => $vehicle2HP,
            'vehicle2_status' => $vehicle2Status,
            'vehicle2_start_time' => $planet['vehicle2_start_time'] ?? null,
            'vehicle2_recall_time' => $planet['vehicle2_recall_time'] ?? null,
            'vehicle2_sensor_lvl' => $planet['vehicle2_sensor_lvl'] ?? 1,
            'last_updated' => date('Y-m-d H:i:s'),
            'iron_production' => $ironProd,
            'energy_production' => $energyProd,
            'iron_storage_limit' => $ironLimit,
            'copper_production' => $copperProd,
            'copper_storage_limit' => $copperLimit,
            'drone_storage_limit' => $droneMultiplier * 100,
            'researched_colors' => $researchedArr,
            'alien_resources' => $newResData,
            'has_drone' => $planet['has_drone'] ?? 0,
            'drone_storage' => $droneStorage,
        ];
    }

    private static function simulateVehicleOne(PDO $db, array $planet, DateTime $now, float $crystalAmount): array
    {
        $vehicleStatus = $planet['vehicle_status'] ?? 'idle';
        $vehicleHP = $planet['vehicle_hp'] ?? 100;
        $vehicleLevel = $planet['vehicle_level'] ?? 0;

        if (($vehicleStatus === 'exploring' || $vehicleStatus === 'returning') && $vehicleLevel > 0) {
            $startTime = new DateTime($planet['vehicle_start_time'] ?? 'now');
            $secondsSinceStart = max(0, $now->getTimestamp() - $startTime->getTimestamp());
            $damageSeconds = $secondsSinceStart;
            if ($vehicleStatus === 'returning') {
                $recallTime = new DateTime($planet['vehicle_recall_time'] ?? 'now');
                $secondsToReturn = max(0, $recallTime->getTimestamp() - $startTime->getTimestamp());
                $damageSeconds = min($secondsSinceStart, $secondsToReturn * 2);
            }

            $baseDamageRate = 0.1;
            $acceleration = 0.003;
            $armorFactor = pow($vehicleLevel, 1.2);
            $totalDamage = ($damageSeconds * ($baseDamageRate + ($damageSeconds * $acceleration))) / $armorFactor;

            if (($planet['research_auto_recall'] ?? 0) && $vehicleStatus === 'exploring' && $totalDamage >= 10) {
                $safeSecs = calculateSafeTime(10, $baseDamageRate, $acceleration, $armorFactor);
                $recallTime = clone $startTime;
                $recallTime->modify('+' . round($safeSecs) . ' seconds');
                $damageSeconds = $safeSecs;
                $totalDamage = 10;
                $vehicleStatus = 'returning';
                safePlanetWrite($db, "UPDATE planets SET vehicle_status = 'returning', vehicle_recall_time = ?, last_updated = ? WHERE id = ?", [$recallTime->format('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $planet['id']]);
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
                    $recallTime = new DateTime($planet['vehicle_recall_time'] ?? 'now');
                    if (($now->getTimestamp() - $recallTime->getTimestamp()) >= ($recallTime->getTimestamp() - $startTime->getTimestamp())) {
                        $sensorLvl = $planet['vehicle_sensor_lvl'] ?? 1;
                        $timeBonus = 1 + (($recallTime->getTimestamp() - $startTime->getTimestamp()) * 0.0005);
                        $crystalAmount += floor(($recallTime->getTimestamp() - $startTime->getTimestamp()) * (0.1 * (1 + ($sensorLvl - 1) * 0.05) * $timeBonus));
                        $vehicleStatus = 'idle';
                        $vehicleHP = 100;
                        safePlanetWrite($db, "UPDATE planets SET crystal_amount = ?, vehicle_status = 'idle', vehicle_hp = 100, last_updated = ? WHERE id = ?", [$crystalAmount, date('Y-m-d H:i:s'), $planet['id']]);
                    }
                }
            }
        }

        return [
            'status' => $vehicleStatus,
            'hp' => $vehicleHP,
            'level' => $vehicleLevel,
            'crystal_amount' => $crystalAmount,
        ];
    }

    private static function simulateVehicleTwo(PDO $db, array $planet, DateTime $now, float $crystalAmount): array
    {
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

            $baseDamageRate = 0.1;
            $acceleration = 0.003;
            $effLvl = 1 + ($vehicle2Level - 1) * 2;
            $armorFactor = pow($effLvl, 1.2);
            $totalDamage = ($damageSeconds * ($baseDamageRate + ($damageSeconds * $acceleration))) / $armorFactor;

            if (($planet['research_auto_recall'] ?? 0) && $vehicle2Status === 'exploring' && $totalDamage >= 10) {
                $safeSecs = calculateSafeTime(10, $baseDamageRate, $acceleration, $armorFactor);
                $recallTime = clone $startTime;
                $recallTime->modify('+' . round($safeSecs) . ' seconds');
                $damageSeconds = $safeSecs;
                $totalDamage = 10;
                $vehicle2Status = 'returning';
                safePlanetWrite($db, "UPDATE planets SET vehicle2_status = 'returning', vehicle2_recall_time = ?, last_updated = ? WHERE id = ?", [$recallTime->format('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $planet['id']]);
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
                    $recallTime = new DateTime($planet['vehicle2_recall_time'] ?? 'now');
                    if (($now->getTimestamp() - $recallTime->getTimestamp()) >= ($recallTime->getTimestamp() - $startTime->getTimestamp())) {
                        $sensorLvl = $planet['vehicle2_sensor_lvl'] ?? 1;
                        $timeBonus = 1 + (($recallTime->getTimestamp() - $startTime->getTimestamp()) * 0.0005);
                        $crystalAmount += floor(($recallTime->getTimestamp() - $startTime->getTimestamp()) * (0.2 * (1 + ($sensorLvl - 1) * 0.10) * $timeBonus));
                        $vehicle2Status = 'idle';
                        $vehicle2HP = 100;
                        safePlanetWrite($db, "UPDATE planets SET crystal_amount = ?, vehicle2_status = 'idle', vehicle2_hp = 100, last_updated = ? WHERE id = ?", [$crystalAmount, date('Y-m-d H:i:s'), $planet['id']]);
                    }
                }
            }
        }

        return [
            'status' => $vehicle2Status,
            'hp' => $vehicle2HP,
            'level' => $vehicle2Level,
            'crystal_amount' => $crystalAmount,
        ];
    }
}
