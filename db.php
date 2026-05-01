<?php
// db.php - SQLite bootstrap entrypoint

error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('UTC');

require_once __DIR__ . '/app/Bootstrap/DatabaseConnection.php';
require_once __DIR__ . '/app/Bootstrap/DatabaseMigrations.php';
require_once __DIR__ . '/app/Support/PlanetHelpers.php';
require_once __DIR__ . '/app/Support/GameStateHelpers.php';
require_once __DIR__ . '/app/Repositories/UserRepository.php';
require_once __DIR__ . '/app/Repositories/PlanetRepository.php';
require_once __DIR__ . '/app/Domain/Planet/PlanetData.php';

$db = createDatabaseConnection(__DIR__ . '/game.sqlite');
initializeDatabase($db);
