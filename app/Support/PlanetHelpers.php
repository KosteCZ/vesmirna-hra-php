<?php

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

function calculateSafeTime($targetDamage, $baseRate, $acceleration, $armorFactor) {
    $inner = pow($baseRate, 2) + 4 * $acceleration * $targetDamage * $armorFactor;
    if ($inner < 0) {
        return 0;
    }
    return (-$baseRate + sqrt($inner)) / (2 * $acceleration);
}

function getRocketPartDefinitions(): array {
    return [
        'rocket_tip' => "Ĺ piÄŤka rakety",
        'rocket_body' => 'Trup rakety',
        'fuel_tank' => "PalivovĂ© nĂˇdrĹľe",
        'jet_engine' => "TryskovĂ˝ motor",
        'satellite' => 'Satelit',
        'solar_panel' => "SolĂˇrnĂ­ panel",
        'seat' => 'Sedadlo',
        'fuel_canister' => "Kanystr s palivem",
        'electronics' => "ElektronickĂ© zaĹ™Ă­zenĂ­",
        'tools' => "NĂˇĹ™adĂ­"
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
