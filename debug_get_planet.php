<?php
session_start();
$_SESSION['user_id'] = 1;
$_GET['action'] = 'get_planet';
ini_set('display_errors', 1);
error_reporting(E_ALL);
require __DIR__ . '/api.php';
