<?php

function createDatabaseConnection(string $dbPath): PDO {
    try {
        $db = new PDO("sqlite:$dbPath");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
    }
}
