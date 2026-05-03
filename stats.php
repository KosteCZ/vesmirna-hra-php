<?php
// stats.php - Public statistics dashboard
session_start();
require_once 'db.php';

// Fetch all players and their planet data
$players = findAllPlayersWithPlanets($db);

$alienMineTotals = [
    'yellow' => 0,
    'red' => 0,
    'blue' => 0,
    'green' => 0,
    'orange' => 0,
    'purple' => 0,
];
foreach ($players as $p) {
    $alienMineTotals['yellow'] += (int) ($p['mine_yellow_lvl'] ?? 0);
    $alienMineTotals['red'] += (int) ($p['mine_red_lvl'] ?? 0);
    $alienMineTotals['blue'] += (int) ($p['mine_blue_lvl'] ?? 0);
    $alienMineTotals['green'] += (int) ($p['mine_green_lvl'] ?? 0);
    $alienMineTotals['orange'] += (int) ($p['mine_orange_lvl'] ?? 0);
    $alienMineTotals['purple'] += (int) ($p['mine_purple_lvl'] ?? 0);
}

$rocketPartDefs = getRocketPartDefinitions();

?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vesmírná hra - statistiky</title>
    <style>
        body {
            background-color: #0f172a;
            color: #e2e8f0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 20px;
        }
        h1, h2 {
            color: #38bdf8;
            border-bottom: 2px solid #334155;
            padding-bottom: 10px;
        }
        .admin-nav {
            margin-bottom: 20px;
            padding: 10px;
            background: #1e293b;
            border-radius: 8px;
        }
        .admin-nav a {
            color: #38bdf8;
            text-decoration: none;
            margin-right: 20px;
            font-weight: bold;
        }
        .admin-nav a:hover {
            text-decoration: underline;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
            background: #1e293b;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #334155;
        }
        th {
            background-color: #334155;
            color: #38bdf8;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
        }
        tr:hover {
            background-color: #2d3748;
        }
        .val-zero {
            color: #64748b;
        }
        .val-positive {
            color: #4ade80;
            font-weight: bold;
        }
        .val-researched {
            color: #4ade80;
            font-weight: bold;
        }
        .val-not-researched {
            color: #f87171;
        }
        .part-count {
            display: inline-block;
            width: 20px;
            text-align: center;
            border-radius: 3px;
            margin-right: 5px;
        }
        .part-full {
            background-color: #059669;
            color: white;
        }
        tfoot td {
            background-color: #0f172a;
            border-top: 2px solid #38bdf8;
            color: #38bdf8;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Vesmírná hra - statistiky</h1>
    
    <div class="admin-nav">
        <a href="index.php">&larr; Zpět do hry</a>
        <a href="#buildings">Budovy</a>
        <a href="#research">Výzkum</a>
        <a href="#rocket">Raketové díly</a>
        <a href="#vehicles">Vozidla</a>
    </div>

    <h2 id="buildings">Úrovně budov</h2>
    <table>
        <thead>
            <tr>
                <th>Hráč</th>
                <th>Důl</th>
                <th>Elektr.</th>
                <th>Sklad</th>
                <th>Důl Cu</th>
                <th>Sklad Cu</th>
                <th>Lab.</th>
                <th>Sklad Lab.</th>
                <th>Dílna</th>
                <th>Důl Kryst.</th>
                <th>Žlutá</th>
                <th>Červená</th>
                <th>Modrá</th>
                <th>Zelená</th>
                <th>Oranž.</th>
                <th>Fialová</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($players as $p): ?>
            <tr>
                <td><strong><?= htmlspecialchars($p['player_name']) ?></strong></td>
                <td><?= $p['mine_level'] ?></td>
                <td><?= $p['solar_plant_level'] ?></td>
                <td><?= $p['warehouse_level'] ?></td>
                <td class="<?= $p['mine_copper_lvl'] > 0 ? 'val-positive' : 'val-zero' ?>"><?= $p['mine_copper_lvl'] ?></td>
                <td class="<?= $p['warehouse_copper_lvl'] > 0 ? 'val-positive' : 'val-zero' ?>"><?= $p['warehouse_copper_lvl'] ?></td>
                <td class="<?= $p['lab_level'] > 0 ? 'val-positive' : 'val-zero' ?>"><?= $p['lab_level'] ?></td>
                <td class="<?= $p['lab_storage_level'] > 0 ? 'val-positive' : 'val-zero' ?>"><?= $p['lab_storage_level'] ?></td>
                <td class="<?= $p['rocket_workshop_level'] > 0 ? 'val-positive' : 'val-zero' ?>"><?= $p['rocket_workshop_level'] ?></td>
                <td class="<?= ($p['secret_crystal_mine_level'] ?? 0) > 0 ? 'val-positive' : 'val-zero' ?>"><?= $p['secret_crystal_mine_level'] ?? 0 ?></td>
                <td class="<?= $p['mine_yellow_lvl'] > 0 ? 'val-positive' : 'val-zero' ?>"><?= $p['mine_yellow_lvl'] ?></td>
                <td class="<?= $p['mine_red_lvl'] > 0 ? 'val-positive' : 'val-zero' ?>"><?= $p['mine_red_lvl'] ?></td>
                <td class="<?= $p['mine_blue_lvl'] > 0 ? 'val-positive' : 'val-zero' ?>"><?= $p['mine_blue_lvl'] ?></td>
                <td class="<?= $p['mine_green_lvl'] > 0 ? 'val-positive' : 'val-zero' ?>"><?= $p['mine_green_lvl'] ?></td>
                <td class="<?= $p['mine_orange_lvl'] > 0 ? 'val-positive' : 'val-zero' ?>"><?= $p['mine_orange_lvl'] ?></td>
                <td class="<?= $p['mine_purple_lvl'] > 0 ? 'val-positive' : 'val-zero' ?>"><?= $p['mine_purple_lvl'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="10">CELKEM mimozemské doly:</td>
                <td><?= $alienMineTotals['yellow'] ?></td>
                <td><?= $alienMineTotals['red'] ?></td>
                <td><?= $alienMineTotals['blue'] ?></td>
                <td><?= $alienMineTotals['green'] ?></td>
                <td><?= $alienMineTotals['orange'] ?></td>
                <td><?= $alienMineTotals['purple'] ?></td>
            </tr>
        </tfoot>
    </table>

    <h2 id="research">Výzkum</h2>
    <table>
        <thead>
            <tr>
                <th>Hráč</th>
                <th>Měď</th>
                <th>Dron 1</th>
                <th>Dron 2</th>
                <th>Dron 3</th>
                <th>Sklad Cu</th>
                <th>Auto-Recall</th>
                <th>Pokr. Lab</th>
                <th>Dílna</th>
                <th>Slot 3</th>
                <th>Důl Kryst.</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($players as $p): ?>
            <tr>
                <td><strong><?= htmlspecialchars($p['player_name']) ?></strong></td>
                <td class="<?= $p['research_copper'] ? 'val-researched' : 'val-not-researched' ?>"><?= $p['research_copper'] ? 'ANO' : 'NE' ?></td>
                <td class="<?= $p['research_drone_upgrade'] ? 'val-researched' : 'val-not-researched' ?>"><?= $p['research_drone_upgrade'] ? 'ANO' : 'NE' ?></td>
                <td class="<?= $p['research_drone_upgrade_2'] ? 'val-researched' : 'val-not-researched' ?>"><?= $p['research_drone_upgrade_2'] ? 'ANO' : 'NE' ?></td>
                <td class="<?= ($p['research_drone_upgrade_3'] ?? 0) ? 'val-researched' : 'val-not-researched' ?>"><?= ($p['research_drone_upgrade_3'] ?? 0) ? 'ANO' : 'NE' ?></td>
                <td class="<?= ($p['research_warehouse_copper'] ?? 0) ? 'val-researched' : 'val-not-researched' ?>"><?= ($p['research_warehouse_copper'] ?? 0) ? 'ANO' : 'NE' ?></td>
                <td class="<?= ($p['research_auto_recall'] ?? 0) ? 'val-researched' : 'val-not-researched' ?>"><?= ($p['research_auto_recall'] ?? 0) ? 'ANO' : 'NE' ?></td>
                <td class="<?= ($p['research_advanced_lab'] ?? 0) ? 'val-researched' : 'val-not-researched' ?>"><?= ($p['research_advanced_lab'] ?? 0) ? 'ANO' : 'NE' ?></td>
                <td class="<?= ($p['research_rocket_workshop'] ?? 0) ? 'val-researched' : 'val-not-researched' ?>"><?= ($p['research_rocket_workshop'] ?? 0) ? 'ANO' : 'NE' ?></td>
                <td class="<?= ($p['research_alien_slot_3'] ?? 0) ? 'val-researched' : 'val-not-researched' ?>"><?= ($p['research_alien_slot_3'] ?? 0) ? 'ANO' : 'NE' ?></td>
                <td class="<?= ($p['research_secret_crystal_mine'] ?? 0) ? 'val-researched' : 'val-not-researched' ?>"><?= ($p['research_secret_crystal_mine'] ?? 0) ? 'ANO' : 'NE' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 id="rocket">Raketové díly (počet / 10)</h2>
    <table>
        <thead>
            <tr>
                <th>Hráč</th>
                <?php foreach ($rocketPartDefs as $label): ?>
                <th><?= htmlspecialchars($label) ?></th>
                <?php endforeach; ?>
                <th>CELKEM</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($players as $p): 
                $parts = normalizeRocketPartsInventory($p['rocket_parts'] ?? '');
                $totalParts = array_sum($parts);
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($p['player_name']) ?></strong></td>
                <?php foreach ($rocketPartDefs as $key => $label): 
                    $count = $parts[$key] ?? 0;
                ?>
                <td>
                    <span class="part-count <?= $count >= 10 ? 'part-full' : '' ?>"><?= $count ?></span>
                </td>
                <?php endforeach; ?>
                <td><strong><?= $totalParts ?></strong> / <?= count($rocketPartDefs) * 10 ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 id="vehicles">Vozidla</h2>
    <table>
        <thead>
            <tr>
                <th>Hráč</th>
                <th>Vozidlo I - Pancéř</th>
                <th>Vozidlo I - Senzory</th>
                <th>Vozidlo II - Pancéř</th>
                <th>Vozidlo II - Senzory</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($players as $p): ?>
            <tr>
                <td><strong><?= htmlspecialchars($p['player_name']) ?></strong></td>
                <td class="<?= ($p['vehicle_level'] ?? 0) > 0 ? 'val-positive' : 'val-zero' ?>"><?= $p['vehicle_level'] ?? 0 ?></td>
                <td class="<?= ($p['vehicle_sensor_lvl'] ?? 1) > 1 ? 'val-positive' : 'val-zero' ?>"><?= $p['vehicle_sensor_lvl'] ?? 1 ?></td>
                <td class="<?= ($p['vehicle2_level'] ?? 0) > 0 ? 'val-positive' : 'val-zero' ?>"><?= $p['vehicle2_level'] ?? 0 ?></td>
                <td class="<?= ($p['vehicle2_sensor_lvl'] ?? 1) > 1 ? 'val-positive' : 'val-zero' ?>"><?= $p['vehicle2_sensor_lvl'] ?? 1 ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
