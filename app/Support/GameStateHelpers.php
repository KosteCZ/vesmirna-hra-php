<?php

function getGameState(PDO $db): string
{
    $stmt = $db->prepare("SELECT value FROM global_settings WHERE key = 'game_state'");
    $stmt->execute();
    return $stmt->fetchColumn() ?: 'COLONIZATION';
}

function getGlobalSetting(PDO $db, string $key, $default = null): ?string
{
    $stmt = $db->prepare("SELECT value FROM global_settings WHERE key = ?");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return ($val !== false) ? $val : $default;
}

function updateGameState(PDO $db, string $newState): void
{
    $stmt = $db->prepare("UPDATE global_settings SET value = ? WHERE key = 'game_state'");
    $stmt->execute([$newState]);
}

function setGlobalSetting(PDO $db, string $key, string $value): void
{
    $stmt = $db->prepare("INSERT INTO global_settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value");
    $stmt->execute([$key, $value]);
}

function checkGameStateTransitions(PDO $db): void
{
    $currentState = getGameState($db);
    
    if ($currentState === 'WIN' || $currentState === 'LOSE') {
        return;
    }

    $totals = calculateGlobalAlienTotals($db);
    $colorsAtLimit = 0;
    foreach ($totals as $amount) {
        if ($amount >= 10000000) {
            $colorsAtLimit++;
        }
    }

    if ($currentState === 'COLONIZATION' && $colorsAtLimit >= 1) {
        updateGameState($db, 'SAND_STORM_COMING_1');
        $eta = (new DateTime())->modify('+12 days')->format('Y-m-d H:i:s');
        setGlobalSetting($db, 'sand_storm_eta', $eta);
    } elseif ($currentState === 'SAND_STORM_COMING_1' && $colorsAtLimit >= 3) {
        updateGameState($db, 'SAND_STORM_COMING_2');
        $fiveDaysEta = (new DateTime())->modify('+5 days');
        $currentEta = getGlobalSetting($db, 'sand_storm_eta');
        if (!$currentEta || new DateTime($currentEta) > $fiveDaysEta) {
            setGlobalSetting($db, 'sand_storm_eta', $fiveDaysEta->format('Y-m-d H:i:s'));
        }
    }
}

function calculateGlobalAlienTotals(PDO $db): array
{
    $sql = "SELECT 
        SUM(res_yellow) as yellow, SUM(res_red) as red, SUM(res_blue) as blue,
        SUM(res_green) as green, SUM(res_orange) as orange, SUM(res_purple) as purple
        FROM planets";
    $stmt = $db->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'yellow' => (float) ($row['yellow'] ?? 0),
        'red' => (float) ($row['red'] ?? 0),
        'blue' => (float) ($row['blue'] ?? 0),
        'green' => (float) ($row['green'] ?? 0),
        'orange' => (float) ($row['orange'] ?? 0),
        'purple' => (float) ($row['purple'] ?? 0),
    ];
}

function isInterstellarGateActive(PDO $db): bool
{
    foreach (calculateGlobalAlienTotals($db) as $amount) {
        if ($amount < 10000000) {
            return false;
        }
    }

    return true;
}
