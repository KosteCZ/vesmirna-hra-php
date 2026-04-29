<?php
// api.php - Game logic API
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();

require_once 'db.php';
require_once __DIR__ . '/app/Http/Api/ExpeditionActions.php';
require_once __DIR__ . '/app/Http/Api/PlanetActions.php';
require_once __DIR__ . '/app/Http/Api/ResearchActions.php';
require_once __DIR__ . '/app/Http/Api/WorkshopActions.php';
require_once __DIR__ . '/app/Http/Api/StatsActions.php';

header('Content-Type: application/json');

const UPGRADE_COST_MULTIPLIER = 100;
const ALLOWED_COLORS = ['yellow', 'red', 'blue', 'green', 'orange', 'purple'];
const ALLOWED_UPGRADE_TYPES = ['mine', 'solar', 'warehouse'];
const ROCKET_WORKSHOP_RESEARCH_COST = 15000;
const ROCKET_WORKSHOP_UPGRADE_COST = 1000000;
const ROCKET_WORKSHOP_PRODUCTION_COST = 10000;
const ROCKET_WORKSHOP_PRODUCTION_DURATION = 28800;
const ROCKET_WORKSHOP_PRODUCTION_COST_2 = 20000;
const ROCKET_WORKSHOP_PRODUCTION_DURATION_2 = 57600;
const ALIEN_SLOT_3_TUBES_COST = 25000;
const ALIEN_SLOT_3_IRON_COST = 2000000;
const ALIEN_SLOT_3_COPPER_COST = 25000;
const SECRET_MINE_RESEARCH_COST_TUBES = 30000;
const SECRET_MINE_UPGRADE_BASE_IRON = 1000000;
const SECRET_MINE_UPGRADE_STEP_IRON = 50000;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Nepřihlášen!']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    $action = $_GET['action'] ?? '';

    $handlers = [
        'handleExpeditionAction',
        'handlePlanetAction',
        'handleResearchAction',
        'handleWorkshopAction',
        'handleStatsAction',
    ];

    foreach ($handlers as $handler) {
        if ($handler($action, $userId, $db)) {
            exit;
        }
    }

    echo json_encode(['error' => 'Neznámá akce!']);
} catch (Exception $e) {
    echo json_encode(['error' => 'Chyba serveru: ' . $e->getMessage()]);
}
