<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/src/Controllers/AuthController.php';

$controller = new AuthController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->register();
} else {
    $controller->showRegisterForm();
}
