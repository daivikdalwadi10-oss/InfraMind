<?php
/**
 * SQLite Database Admin - Simple and Clean
 * Uses phpLiteAdmin for better SQLite management
 */

// Start session and check authentication
session_start();

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

$_SESSION['admin_login_time'] = time();

// Configure phpLiteAdmin
$directory = realpath(__DIR__ . '/..');
$databases = [
    [
        'path' => $directory . '/database.sqlite',
        'name' => 'InfraMind Database'
    ]
];

// Set password (already authenticated via admin-login)
$password = 'authenticated';
$_POST['password'] = $password;

// Security headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('X-Frame-Options: SAMEORIGIN');

// Include phpLiteAdmin
include __DIR__ . '/phpliteadmin.php';
