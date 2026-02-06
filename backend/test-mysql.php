<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=inframind', 'inframind', 'inframindpass');
    echo "Connection successful!\n";
    $result = $pdo->query("SELECT VERSION()");
    echo "MySQL Version: " . $result->fetchColumn() . "\n";
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
