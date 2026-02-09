<?php

$pdo = new PDO('sqlite:c:/workspace/inframind/backend/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$info = $pdo->query('PRAGMA table_info(audit_logs)')->fetchAll(PDO::FETCH_ASSOC);
foreach ($info as $row) {
    echo $row['name'] . ' | ' . $row['type'] . ' | ' . $row['pk'] . PHP_EOL;
}
$schema = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='audit_logs'")->fetch(PDO::FETCH_ASSOC);
if ($schema && isset($schema['sql'])) {
    echo "-- schema --" . PHP_EOL;
    echo $schema['sql'] . PHP_EOL;
}
