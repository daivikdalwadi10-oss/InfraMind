<?php
/**
 * SIMPLE SQLITE DATABASE VIEWER
 * Replaces Adminer with a lightweight, working alternative
 * Provides secure database access without the language issues
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

// Database file path
$dbPath = __DIR__ . '/../sqlite.db';

// Connect to database
try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . htmlspecialchars($e->getMessage()));
}

// Get all tables
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

// Get selected table
$selectedTable = isset($_GET['table']) && in_array($_GET['table'], $tables) ? $_GET['table'] : ($tables[0] ?? null);

// Get table data
$tableData = [];
if ($selectedTable) {
    $tableData = $db->query("SELECT * FROM " . $selectedTable)->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SQLite Database Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 28px; }
        .logout { background: #ff6b6b; padding: 8px 16px; border-radius: 4px; text-decoration: none; color: white; }
        .logout:hover { background: #ff5252; }
        
        .content { display: flex; min-height: 600px; }
        .sidebar { 
            width: 250px; 
            background: #f8f9fa; 
            border-right: 1px solid #e9ecef;
            padding: 20px;
            overflow-y: auto;
        }
        .sidebar h3 { font-size: 12px; color: #666; margin-bottom: 15px; text-transform: uppercase; }
        .table-list { list-style: none; }
        .table-list li { margin-bottom: 8px; }
        .table-list a {
            display: block;
            padding: 10px;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            background: white;
            border: 1px solid #dee2e6;
            transition: all 0.2s;
        }
        .table-list a:hover { background: #e7f3ff; border-color: #667eea; }
        .table-list a.active { background: #667eea; color: white; border-color: #667eea; }
        
        .main {
            flex: 1;
            padding: 30px;
            overflow-x: auto;
        }
        .main h2 { margin-bottom: 20px; color: #333; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #333;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        tr:hover { background: #f8f9fa; }
        
        .empty { 
            text-align: center; 
            padding: 40px;
            color: #999;
        }
        .stats {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .stat {
            text-align: center;
        }
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 SQLite Database Manager</h1>
            <a href="admin-logout.php" class="logout">Logout</a>
        </div>
        
        <div class="content">
            <div class="sidebar">
                <h3>Tables (<?php echo count($tables); ?>)</h3>
                <ul class="table-list">
                    <?php foreach ($tables as $table): ?>
                        <li>
                            <a href="?table=<?php echo urlencode($table); ?>" class="<?php echo $table === $selectedTable ? 'active' : ''; ?>">
                                📋 <?php echo htmlspecialchars($table); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="main">
                <?php if ($selectedTable): ?>
                    <h2><?php echo htmlspecialchars($selectedTable); ?></h2>
                    
                    <div class="stats">
                        <div class="stat">
                            <div class="stat-value"><?php echo count($tableData); ?></div>
                            <div class="stat-label">Rows</div>
                        </div>
                        <?php if (!empty($tableData)): ?>
                            <div class="stat">
                                <div class="stat-value"><?php echo count($tableData[0]); ?></div>
                                <div class="stat-label">Columns</div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (empty($tableData)): ?>
                        <div class="empty">
                            <p>📭 No data in this table</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <?php foreach (array_keys($tableData[0]) as $column): ?>
                                        <th><?php echo htmlspecialchars($column); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tableData as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $value): ?>
                                            <td>
                                                <?php 
                                                    if ($value === null) {
                                                        echo '<em style="color:#999;">NULL</em>';
                                                    } else {
                                                        echo htmlspecialchars(is_string($value) ? $value : json_encode($value));
                                                    }
                                                ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty">
                        <p>👉 Select a table from the left to view its data</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
