<?php
/**
 * Simple SQLite Database Viewer
 * Access: http://localhost:8000/db-viewer.php
 */

$dbPath = __DIR__ . '/../database.sqlite';

if (!file_exists($dbPath)) {
    die('Database file not found: ' . $dbPath);
}

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

$table = $_GET['table'] ?? '';
$action = $_GET['action'] ?? 'tables';

?>
<!DOCTYPE html>
<html>
<head>
    <title>InfraMind Database Viewer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; background: #0f172a; color: #e2e8f0; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { color: #60a5fa; margin-bottom: 10px; font-size: 24px; }
        h2 { color: #93c5fd; margin: 20px 0 10px; font-size: 18px; }
        .info { background: #1e293b; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #60a5fa; }
        .tables { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-bottom: 30px; }
        .table-link { background: #1e293b; padding: 12px 16px; border-radius: 6px; text-decoration: none; color: #60a5fa; display: block; transition: all 0.2s; border: 1px solid #334155; }
        .table-link:hover { background: #334155; border-color: #60a5fa; }
        .table-link .count { color: #94a3b8; font-size: 12px; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 8px; overflow: hidden; }
        th { background: #334155; padding: 12px; text-align: left; font-weight: 600; color: #60a5fa; border-bottom: 2px solid #475569; }
        td { padding: 10px 12px; border-bottom: 1px solid #334155; }
        tr:hover { background: #2d3748; }
        .btn { display: inline-block; padding: 8px 16px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; margin-right: 10px; font-size: 14px; }
        .btn:hover { background: #2563eb; }
        .btn-back { background: #475569; }
        .btn-back:hover { background: #64748b; }
        code { background: #334155; padding: 2px 6px; border-radius: 4px; font-size: 13px; color: #93c5fd; }
        .sql-query { background: #1e293b; padding: 12px; border-radius: 6px; margin: 10px 0; border-left: 3px solid #3b82f6; }
        .empty { text-align: center; padding: 40px; color: #64748b; }
    </style>
</head>
<body>
<div class="container">
    <h1>🗄️ InfraMind Database Viewer</h1>
    
    <div class="info">
        <strong>Database:</strong> <code><?= basename($dbPath) ?></code><br>
        <strong>Path:</strong> <code><?= $dbPath ?></code><br>
        <strong>Size:</strong> <?= number_format(filesize($dbPath) / 1024, 2) ?> KB
    </div>

    <?php if ($action === 'tables' || empty($table)): ?>
        <h2>📊 Database Tables</h2>
        <div class="tables">
            <?php
            $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tables as $tbl) {
                $count = $db->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn();
                echo "<a href='?table=$tbl' class='table-link'>";
                echo "<div><strong>$tbl</strong></div>";
                echo "<div class='count'>$count rows</div>";
                echo "</a>";
            }
            ?>
        </div>

    <?php elseif ($action === 'view' || $table): ?>
        <a href="?" class="btn btn-back">← Back to Tables</a>
        <h2>📋 Table: <?= htmlspecialchars($table) ?></h2>
        
        <?php
        $stmt = $db->query("SELECT * FROM `$table` LIMIT 100");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rows)) {
            echo "<div class='empty'>No data in this table</div>";
        } else {
            echo "<table>";
            echo "<thead><tr>";
            foreach (array_keys($rows[0]) as $column) {
                echo "<th>" . htmlspecialchars($column) . "</th>";
            }
            echo "</tr></thead><tbody>";
            
            foreach ($rows as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    $display = $value;
                    if (strlen($value) > 100) {
                        $display = substr($value, 0, 100) . '...';
                    }
                    echo "<td>" . htmlspecialchars($display) . "</td>";
                }
                echo "</tr>";
            }
            echo "</tbody></table>";
            echo "<p style='margin-top: 10px; color: #64748b; font-size: 12px;'>Showing first 100 rows</p>";
        }
        ?>
    <?php endif; ?>

    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #334155; color: #64748b; font-size: 12px;">
        <strong>Need full features?</strong> Use Adminer at <a href="adminer.php?sqlite=<?= urlencode($dbPath) ?>" style="color: #60a5fa;">adminer.php</a>
    </div>
</div>
</body>
</html>
