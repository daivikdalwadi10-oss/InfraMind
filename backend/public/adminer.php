<?php
/**
 * ADMINER DATABASE MANAGER
 * Secure access with authentication required
 */

// Start session
session_start();

// Verify authentication
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    header('Location: admin-login.php');
    exit;
}

// Check timeout
if (isset($_SESSION['admin_login_time']) && time() - $_SESSION['admin_login_time'] > 3600) {
    session_destroy();
    header('Location: admin-login.php');
    exit;
}

// Update activity
$_SESSION['admin_login_time'] = time();

// Headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

// Load Adminer
require 'adminer-original.php';
