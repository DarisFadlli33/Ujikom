<?php
/**
 * Entry Point: Login OOP Style
 * File ini menggabungkan logic login dengan OOP structure
 */

require_once __DIR__ . '/app/bootstrap-oop.php';

// Redirect jika sudah login
if (\App\Services\AuthService::isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Create controller
$authService = app('auth');
$controller = new \App\Controllers\AuthController($authService);

// Route ke controller
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->login();
} else {
    $controller->loginForm();
}
