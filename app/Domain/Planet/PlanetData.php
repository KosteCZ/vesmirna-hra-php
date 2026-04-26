<?php

require_once __DIR__ . '/PlanetSimulator.php';

function getPlanetData($userId, PDO $db) {
    try {
        $planet = findRawPlanetByUserId($db, (int) $userId);
        if (!$planet) {
            return null;
        }

        return PlanetSimulator::simulate($db, $planet);
    } catch (Exception $e) {
        return isset($planet) ? $planet : null;
    }
}
