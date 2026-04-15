<?php
/**
 * Entry Point: Logout OOP Style
 */

require_once __DIR__ . '/app/bootstrap-oop.php';

\App\Services\AuthService::logout();
