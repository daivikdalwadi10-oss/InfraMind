<?php
/**
 * Admin Logout
 */

session_start();
session_destroy();

// Redirect to login page with message
header('Location: admin-login.php?logged_out=1');
exit;
