<?php
/**
 * InfraMind Database Viewer
 * Simple, clean SQLite database browser
 */

session_start();

// Simple password protection - enter "admin" to access
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['access_code'])) {
    if ($_POST['access_code'] === 'admin') {
        $_SESSION['db_access'] = true;
    }
}

if (!isset($_SESSION['db_access'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Database Access</title>
        <style>
            body { margin: 0; padding: 0; display: flex; align-items: center; justify-content: center; height: 100vh; background: #0f172a; font-family: sans-serif; }
            form { background: #1e293b; padding: 2rem; border-radius: 1rem; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
            input { padding: 0.75rem; font-size: 1rem; border: 1px solid #334155; background: #334155; color: white; border-radius: 0.5rem; width: 250px; }
            button { margin-top: 1rem; padding: 0.75rem 1.5rem; background: #3b82f6; color: white; border: none; border-radius: 0.5rem; cursor: pointer; width: 100%; font-size: 1rem; }
            button:hover { background: #2563eb; }
            h2 { color: #60a5fa; margin-top: 0; }
        </style>
    </head>
    <body>
        <form method="POST">
            <h2>🗄️ Database Access</h2>
            <input type="password" name="access_code" placeholder="Enter access code" autofocus>
            <button type="submit">Access Database</button>
            <p style="color: #94a3b8; font-size: 0.875rem; margin-top: 1rem;">Hint: admin</p>
        </form>
    </body>
    </html>
    <?php
    exit;
}

$_SESSION['admin_login_time'] = time();

$dbPath = realpath(__DIR__ . '/../database.sqlite');
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$action = $_GET['action'] ?? 'tables';
$table = $_GET['table'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InfraMind Database</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; }
        .header { background: #1e293b; padding: 1rem 2rem; border-bottom: 2px solid #3b82f6; }
        .header h1 { font-size: 1.5rem; color: #60a5fa; }
        .container { display: flex; height: calc(100vh - 70px); }
        .sidebar { width: 250px; background: #1e293b; padding: 1rem; overflow-y: auto; border-right: 1px solid #334155; }
        .sidebar h2 { font-size: 1rem; color: #94a3b8; margin-bottom: 1rem; }
        .sidebar a { display: block; padding: 0.75rem; margin-bottom: 0.5rem; background: #334155; color: #e2e8f0; text-decoration: none; border-radius: 0.5rem; transition: all 0.2s; }
        .sidebar a:hover, .sidebar a.active { background: #3b82f6; color: white; }
        .content { flex: 1; padding: 2rem; overflow-y: auto; }
        table { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 0.5rem; overflow: hidden; }
        th { background: #334155; padding: 1rem; text-align: left; color: #60a5fa; font-weight: 600; }
        td { padding: 0.75rem 1rem; border-top: 1px solid #334155; }
        tr:hover { background: #334155; }
        .badge { display: inline-block; padding: 0.25rem 0.75rem; background: #3b82f6; color: white; border-radius: 9999px; font-size: 0.875rem; }
        .success { background: #10b981; }
        .warning { background: #f59e0b; }
        .danger { background: #ef4444; }
        .info { background: #06b6d4; }
        .logout { float: right; padding: 0.5rem 1rem; background: #ef4444; color: white; text-decoration: none; border-radius: 0.5rem; }
        .logout:hover { background: #dc2626; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🗄️ InfraMind Database</h1>
        <a href="admin-logout.php" class="logout">Logout</a>
    </div>
    <div class="container">
        <div class="sidebar">
            <h2>Tables</h2>
            <?php
            $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tables as $t) {
                $active = $table === $t ? 'active' : '';
                $count = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
                echo "<a href='?action=browse&table=$t' class='$active'>$t <span class='badge'>$count</span></a>";
            }
            ?>
        </div>
        <div class="content">
            <?php if ($action === 'tables' || !$table): ?>
                <h2 style="margin-bottom: 1rem; color: #60a5fa;">Database Overview</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Table</th>
                            <th>Records</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tables as $t):
                            $count = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($t) ?></strong></td>
                            <td><span class="badge info"><?= $count ?> rows</span></td>
                            <td><a href="?action=browse&table=<?= $t ?>" style="color: #60a5fa;">Browse →</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif ($action === 'browse' && $table): ?>
                <h2 style="margin-bottom: 1rem; color: #60a5fa;">Table: <?= htmlspecialchars($table) ?></h2>
                <?php
                $limit = 100;
                $offset = (int)($_GET['page'] ?? 0) * $limit;
                $stmt = $db->prepare("SELECT * FROM `$table` LIMIT $limit OFFSET $offset");
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $total = $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                
                if ($rows):
                ?>
                <p style="margin-bottom: 1rem; color: #94a3b8;">Showing <?= count($rows) ?> of <?= $total ?> records</p>
                <table>
                    <thead>
                        <tr>
                            <?php foreach (array_keys($rows[0]) as $col): ?>
                                <th><?= htmlspecialchars($col) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($row as $val): ?>
                                <td><?= htmlspecialchars(substr($val ?? '', 0, 100)) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="color: #94a3b8;">No records found.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
