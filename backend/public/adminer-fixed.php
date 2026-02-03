<?php
/**
 * SECURED ADMINER WITH LANGUAGE FIX
 * Includes Adminer but fixes numeric label display issue
 * Note: session_start() is already called in adminer.php
 */

// Session already started by adminer.php

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

// Clear cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

// Capture output and fix language issues
ob_start();

// Include the original Adminer
include __DIR__ . '/adminer-original.php';

// Get the output
$output = ob_get_clean();

// Fix any numeric label issues
// These are common translations in Adminer that appear as numbers
$replacements = array(
    // Common Adminer numeric labels and their English equivalents
    '>1</' => '>Save</',           // Save button
    '>2</' => '>Insert</',         // Insert button
    '>3</' => '>Delete</',         // Delete button
    '>4</' => '>Edit</',           // Edit button  
    '>5</' => '>Select</',         // Select button
    '>0</' => '>Database</',       // Database label
    'value="1"' => 'value="Save"',
    'value="2"' => 'value="Insert"',
    'value="3"' => 'value="Delete"',
    'value="4"' => 'value="Edit"',
    'value="5"' => 'value="Select"',
    // More comprehensive search/replace for common patterns
);

foreach ($replacements as $search => $replace) {
    if (strpos($output, $search) !== false) {
        $output = str_replace($search, $replace, $output);
    }
}

// Output the fixed HTML
echo $output;
