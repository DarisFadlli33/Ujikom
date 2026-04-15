<?php
/**
 * Entry Point: Task Actions OOP Style
 */

require_once __DIR__ . '/app/bootstrap-oop.php';

use App\Services\AuthService;

// Cek login
AuthService::requireLogin();

// Get action
$action = $_POST['action'] ?? '';

// Create task service
$taskService = app('task');
$controller = new \App\Controllers\TaskController($taskService);

// Route ke method yang sesuai
switch ($action) {
    case 'add':
        $controller->add();
        break;
    
    case 'edit_admin':
        $controller->editAdmin();
        break;
    
    case 'delete_admin':
        $controller->deleteAdmin();
        break;
    
    case 'bulk_delete_tasks':
        $controller->bulkDeleteTasks();
        break;
    
    default:
        header('HTTP/1.1 400 Bad Request');
        die('Action tidak dikenali');
}
