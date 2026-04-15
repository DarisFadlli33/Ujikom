<?php
/**
 * Database Cleanup Script
 * 
 * Menghapus duplicate data dari database dan memastikan data integrity
 * 
 * Jalankan dari browser: http://localhost/taskhub/cleanup.php
 * Atau dari command line: php cleanup.php
 */

require_once __DIR__ . '/app/bootstrap-oop.php';

use App\Services\AuthService;

// Require login
AuthService::requireAdmin();

// Start cleanup
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Database Cleanup</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;}.error{color:red;}.info{color:blue;} code{background:#eee;padding:5px;}</style>";
echo "</head><body>";
echo "<h1>Database Cleanup Report</h1>";
echo "<hr>";

$stats = [
    'duplicates_removed' => 0,
    'errors' => [],
    'total_operations' => 0,
    'status' => 'pending'
];

try {
    // 1. Remove duplicate users (keep first by ID)
    echo "<h2>1. Checking Duplicate Users</h2>";
    
    $stmt = $pdo->query(
        "SELECT username, COUNT(*) as cnt FROM users GROUP BY username HAVING cnt > 1"
    );
    
    $duplicates = $stmt->fetchAll();
    
    if ($duplicates) {
        foreach ($duplicates as $dup) {
            echo "<p class='info'>Found duplicate username: <code>{$dup['username']}</code> (count: {$dup['cnt']})</p>";
            
            // Keep first, delete others
            $stmt = $pdo->prepare(
                "DELETE FROM users WHERE username = ? AND id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM users WHERE username = ? ORDER BY id LIMIT 1
                    ) as first
                )"
            );
            $stmt->execute([$dup['username'], $dup['username']]);
            $deleted = $stmt->rowCount();
            
            echo "<p class='success'>✓ Removed {$deleted} duplicate(s)</p>";
            $stats['duplicates_removed'] += $deleted;
        }
    } else {
        echo "<p class='success'>✓ No duplicate users found</p>";
    }
    
    echo "<hr>";
    
    // 2. Remove orphaned tasks (tasks with non-existent users)
    echo "<h2>2. Checking Orphaned Tasks</h2>";
    
    $stmt = $pdo->query(
        "SELECT COUNT(*) FROM tasks WHERE user_id NOT IN (SELECT id FROM users)"
    );
    
    $orphanedCount = $stmt->fetchColumn();
    
    if ($orphanedCount > 0) {
        echo "<p class='info'>Found {$orphanedCount} orphaned task(s)</p>";
        
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE user_id NOT IN (SELECT id FROM users)");
        $stmt->execute();
        
        echo "<p class='success'>✓ Removed {$orphanedCount} orphaned task(s)</p>";
        $stats['duplicates_removed'] += $orphanedCount;
    } else {
        echo "<p class='success'>✓ No orphaned tasks found</p>";
    }
    
    echo "<hr>";
    
    // 3. Check invalid status IDs
    echo "<h2>3. Checking Invalid Task Status IDs</h2>";
    
    $stmt = $pdo->query(
        "SELECT COUNT(*) FROM tasks WHERE status_id NOT IN (SELECT id FROM task_statuses)"
    );
    
    $invalidStatus = $stmt->fetchColumn();
    
    if ($invalidStatus > 0) {
        echo "<p class='info'>Found {$invalidStatus} task(s) with invalid status</p>";
        
        // Set to default status (open = 1)
        $stmt = $pdo->prepare(
            "UPDATE tasks SET status_id = 1 WHERE status_id NOT IN (SELECT id FROM task_statuses)"
        );
        $stmt->execute();
        
        echo "<p class='success'>✓ Fixed {$invalidStatus} task status(es) to default</p>";
        $stats['duplicates_removed'] += $invalidStatus;
    } else {
        echo "<p class='success'>✓ All task statuses are valid</p>";
    }
    
    echo "<hr>";
    
    // 4. Check invalid role IDs
    echo "<h2>4. Checking Invalid Role IDs</h2>";
    
    $stmt = $pdo->query(
        "SELECT COUNT(*) FROM users WHERE role_id NOT IN (SELECT id FROM roles)"
    );
    
    $invalidRole = $stmt->fetchColumn();
    
    if ($invalidRole > 0) {
        echo "<p class='info'>Found {$invalidRole} user(s) with invalid role</p>";
        
        // Get default role user ID
        $roleId = $pdo->query("SELECT id FROM roles WHERE name = 'user' LIMIT 1")->fetchColumn();
        
        $stmt = $pdo->prepare(
            "UPDATE users SET role_id = ? WHERE role_id NOT IN (SELECT id FROM roles)"
        );
        $stmt->execute([$roleId]);
        
        echo "<p class='success'>✓ Fixed {$invalidRole} user role(s) to default</p>";
        $stats['duplicates_removed'] += $invalidRole;
    } else {
        echo "<p class='success'>✓ All user roles are valid</p>";
    }
    
    echo "<hr>";
    
    // 5. Remove orphaned attachments
    echo "<h2>5. Checking Orphaned Attachments</h2>";
    
    $stmt = $pdo->query(
        "SELECT COUNT(*) FROM attachments WHERE task_id NOT IN (SELECT id FROM tasks)"
    );
    
    $orphanedAttach = $stmt->fetchColumn();
    
    if ($orphanedAttach > 0) {
        echo "<p class='info'>Found {$orphanedAttach} orphaned attachment(s)</p>";
        
        $stmt = $pdo->prepare(
            "DELETE FROM attachments WHERE task_id NOT IN (SELECT id FROM tasks)"
        );
        $stmt->execute();
        
        echo "<p class='success'>✓ Removed {$orphanedAttach} orphaned attachment(s)</p>";
        $stats['duplicates_removed'] += $orphanedAttach;
    } else {
        echo "<p class='success'>✓ No orphaned attachments found</p>";
    }
    
    echo "<hr>";
    
    // 6. Database Statistics
    echo "<h2>6. Database Statistics</h2>";
    
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $taskCount = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
    $attachCount = $pdo->query("SELECT COUNT(*) FROM attachments")->fetchColumn();
    $roleCount = $pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn();
    $statusCount = $pdo->query("SELECT COUNT(*) FROM task_statuses")->fetchColumn();
    
    echo "<ul>";
    echo "<li>Total Users: <code>$userCount</code></li>";
    echo "<li>Total Tasks: <code>$taskCount</code></li>";
    echo "<li>Total Attachments: <code>$attachCount</code></li>";
    echo "<li>Total Roles: <code>$roleCount</code></li>";
    echo "<li>Total Task Statuses: <code>$statusCount</code></li>";
    echo "</ul>";
    
    echo "<hr>";
    
    // Summary
    echo "<h2>Cleanup Summary</h2>";
    echo "<p class='success'><strong>✓ Total Duplicates/Orphaned Data Removed: {$stats['duplicates_removed']}</strong></p>";
    echo "<p class='success'><strong>✓ Database cleanup completed successfully!</strong></p>";
    
    $stats['status'] = 'success';
    
} catch (PDOException $e) {
    $stats['status'] = 'error';
    $stats['errors'][] = $e->getMessage();
    
    echo "<p class='error'><strong>✗ Error during cleanup:</strong></p>";
    echo "<p class='error'><code>" . htmlspecialchars($e->getMessage()) . "</code></p>";
}

echo "<hr>";
echo "<p><a href='dashboard.php'>&lt; Back to Dashboard</a></p>";
echo "</body></html>";
