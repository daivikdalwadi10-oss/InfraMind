<?php
/**
 * Secure Adminer Wrapper
 * Requires authentication before allowing access to database admin interface
 */

session_start();

// Check if user is authenticated
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    // Redirect to login page
    header('Location: admin-login.php');
    exit;
}

// Check session timeout (1 hour)
if (isset($_SESSION['admin_login_time']) && time() - $_SESSION['admin_login_time'] > 3600) {
    session_destroy();
    header('Location: admin-login.php');
    exit;
}

// Update last activity time
$_SESSION['admin_login_time'] = time();

// Include the actual Adminer application
require_once __DIR__ . '/adminer-app.php';
