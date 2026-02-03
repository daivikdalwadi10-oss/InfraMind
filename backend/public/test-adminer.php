<?php
/**
 * Test page to verify Adminer language rendering
 * Access at: http://localhost:8000/test-adminer.php
 */

session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;

// If not logged in, show a simple test form
if (!$isLoggedIn) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Adminer Test</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            .container { max-width: 600px; margin: 0 auto; }
            .info { background: #e3f2fd; padding: 20px; border-radius: 4px; margin-bottom: 20px; }
            code { background: #f5f5f5; padding: 2px 6px; font-family: monospace; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Adminer Language Test</h1>
            <div class="info">
                <p><strong>Status:</strong> Not logged in</p>
                <p>Log in to admin-login.php first (admin / AdminPassword123!)</p>
                <p>After login, access <code>/adminer.php</code> to check if labels display correctly (not as numbers).</p>
            </div>
            <p><a href="admin-login.php">Go to Login</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// If logged in, show info
?>
<!DOCTYPE html>
<html>
<head>
    <title>Adminer Info</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 600px; margin: 0 auto; }
        .info { background: #c8e6c9; padding: 20px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✓ You are logged in</h1>
        <div class="info">
            <p><strong>Session Status:</strong> Authenticated</p>
            <p><strong>Login Time:</strong> <?php echo date('Y-m-d H:i:s', $_SESSION['admin_login_time']); ?></p>
        </div>
        <p><a href="adminer.php" style="font-size: 18px; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">
            → Open Adminer
        </a></p>
        <p><small>If Adminer shows numbers instead of labels, clear your browser cache and refresh.</small></p>
    </div>
</body>
</html>
