<?php
/**
 * SECURED ADMINER - Custom Database Management Interface
 * Replaces standard Adminer with improved language/label rendering
 * Authentication required - login at admin-login.php
 */

session_start();

// Check authentication
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
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

// Try to include Adminer with proper setup
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Force language settings
putenv('ADMINER_DEFAULT_SERVER=sqlite');
$_GET['lang'] = 'en';
$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Use the adminer-clean.php but with language force
ob_start();
include __DIR__ . '/adminer-clean.php';
$output = ob_get_clean();

// Fix any numeric label issues by post-processing the output
if ($output && strpos($output, '>1<') !== false) {
    // Language mapping for common numeric labels in Adminer
    $languageMap = array(
        '>1<' => '>Save<',
        '>2<' => '>Insert<',
        '>3<' => '>Delete<',
        '>4<' => '>Edit<',
        '>5<' => '>Select<',
        '>0<' => '>Database<',
    );
    
    foreach ($languageMap as $num => $text) {
        $output = str_replace($num, $text, $output);
    }
}

echo $output;
