<?php
/**
 * Entry Point: Register OOP Style
 */

require_once __DIR__ . '/app/bootstrap-oop.php';

// Create controller
$authService = app('auth');
$controller = new \App\Controllers\AuthController($authService);

// Route ke controller
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->register();
} else {
    $controller->registerForm();
}
