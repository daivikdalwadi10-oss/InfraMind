<?php
/**
 * Secure phpMyAdmin Login Page
 * Protects database admin interface with username/password authentication
 */

session_start();

// Admin credentials (change these in production!)
const ADMIN_USER = 'admin';
const ADMIN_PASSWORD = 'AdminPassword123!'; // CHANGE THIS IN PRODUCTION

// Check if already authenticated
if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
    // Redirect to phpMyAdmin
    header('Location: http://localhost:8080');
    exit;
}

// Handle login attempt
$error = '';
$success = '';

if (isset($_GET['logged_out']) && $_GET['logged_out'] === '1') {
    $success = 'You have been logged out successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === ADMIN_USER && $password === ADMIN_PASSWORD) {
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_login_time'] = time();
        header('Location: http://localhost:8080');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}

// Session timeout (1 hour)
if (isset($_SESSION['admin_login_time']) && time() - $_SESSION['admin_login_time'] > 3600) {
    session_destroy();
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Admin - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .login-button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .login-button:active {
            transform: translateY(0);
        }
        
        .error-message {
            background-color: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #c33;
        }
        
        .info-box {
            background-color: #f0f4ff;
            border: 1px solid #d0deff;
            padding: 12px;
            border-radius: 4px;
            font-size: 12px;
            color: #555;
            margin-top: 20px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🔐 Database Admin</h1>
            <p>phpMyAdmin Access</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div style="background-color: #efe; color: #3c3; padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; border-left: 4px solid #3c3;"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter password" required>
            </div>
            
            <button type="submit" class="login-button">Login</button>
            
            <div class="info-box">
                <strong>ℹ️ Default Credentials:</strong><br>
                Username: <code>admin</code><br>
                Password: <code>AdminPassword123!</code><br>
                <br>
                <strong>⚠️ Important:</strong> Change these credentials in production!
            </div>
        </form>
    </div>
</body>
</html>
