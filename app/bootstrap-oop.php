<?php

/**
 * OOP Bootstrap - Inisialisasi aplikasi dengan OOP structure
 */

session_start();

// Define base path
define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');

// Autoloader untuk namespace App\*
spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') === 0) {
        $classPath = str_replace('\\', '/', $class);
        $file = BASE_PATH . '/' . $classPath . '.php';
        
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Database connection
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=taskhub;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die('Database Error: ' . $e->getMessage());
}

// Set PDO untuk models
use App\Models\BaseModel;
BaseModel::setPDO($pdo);

// Helper functions
function app($class) {
    static $instances = [];
    
    if (!isset($instances[$class])) {
        global $pdo;
        
        // Factory untuk membuat instances
        switch ($class) {
            case 'auth':
                $userRepo = new \App\Repositories\UserRepository($pdo);
                $roleRepo = new \App\Repositories\RoleRepository($pdo);
                $instances[$class] = new \App\Services\AuthService($userRepo, $roleRepo);
                break;
            
            case 'task':
                $taskRepo = new \App\Repositories\TaskRepository($pdo);
                $statusRepo = new \App\Repositories\TaskStatusRepository($pdo);
                $instances[$class] = new \App\Services\TaskService($taskRepo, $statusRepo);
                break;
            
            case 'users':
                $instances[$class] = new \App\Repositories\UserRepository($pdo);
                break;
            
            case 'tasks':
                $instances[$class] = new \App\Repositories\TaskRepository($pdo);
                break;
            
            case 'roles':
                $instances[$class] = new \App\Repositories\RoleRepository($pdo);
                break;
            
            case 'statuses':
                $instances[$class] = new \App\Repositories\TaskStatusRepository($pdo);
                break;
            
            default:
                throw new \RuntimeException("Service $class tidak ditemukan");
        }
    }
    
    return $instances[$class];
}

// Short alias untuk auth service
function auth() {
    return app('auth');
}

// Short alias untuk task service
function tasks() {
    return app('tasks');
}
