<?php
// Router Dashboard - Arahkan admin ke admin/dashboard.php, user ke user/dashboard.php
require_once __DIR__ . '/bootstrap.php';

// Validasi login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$role = $_SESSION['role'] ?? 'user';

// Arahkan berdasarkan role
if ($role === 'admin') {
    header('Location: admin/dashboard.php');
    exit;
} else {
    header('Location: user/dashboard.php'); 
    exit;
}
