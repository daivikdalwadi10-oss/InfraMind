<?php
/**
 * ADMINER DATABASE MANAGER
 * Secure access with authentication required
 * Auto-configures SQLite database connection
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Start session
session_start();

// Verify authentication
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    header('Location: admin-login.php');
    exit;
}

// Check timeout (1 hour)
if (isset($_SESSION['admin_login_time']) && time() - $_SESSION['admin_login_time'] > 3600) {
    session_destroy();
    header('Location: admin-login.php');
    exit;
}

// Update activity
$_SESSION['admin_login_time'] = time();

// Cache control headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('X-Frame-Options: SAMEORIGIN');

// Auto-connect to SQLite database
$_GET['sqlite'] = realpath(__DIR__ . '/../database.sqlite');
$_GET['username'] = '';

// Load Adminer directly
include __DIR__ . '/adminer-original.php';
