<?php
// Simple test to see if basic routing works
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "Testing basic PHP...\n";

require_once __DIR__ . '/../vendor/autoload.php';
echo "Autoloader loaded\n";

use InfraMind\Core\Config;
echo "Config class loaded\n";

Config::load(__DIR__ . '/../.env');
echo "Config loaded\n";

echo "DB_DRIVER: " . $_ENV['DB_DRIVER'] . "\n";
echo "APP_ENV: " . $_ENV['APP_ENV'] . "\n";

use InfraMind\Core\Database;
echo "Database class loaded\n";

$db = Database::getInstance();
echo "Database connected!\n";

echo "\nEverything works!\n";
