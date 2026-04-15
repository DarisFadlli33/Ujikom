<?php
function requireLogin() {
    if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
}
function requireRole(string $role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) { header('Location: dashboard.php'); exit; }
}
function requireUser()  { requireRole('user'); }
function requireAdmin() { requireRole('admin'); }
