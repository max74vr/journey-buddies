<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/src/Controllers/TravelController.php';

$controller = new TravelController();
$controller->index();
