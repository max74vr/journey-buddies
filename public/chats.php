<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/src/Controllers/ChatController.php';

$controller = new ChatController();
$controller->index();
