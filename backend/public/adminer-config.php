<?php
/**
 * Adminer Auto-Login Plugin
 * Automatically connects based on backend .env settings
 */

require_once __DIR__ . '/../vendor/autoload.php';

use InfraMind\Core\Config;

Config::load(__DIR__ . '/../.env');

$driver = $_ENV['DB_DRIVER'] ?? 'sqlite';
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '';
$dbName = $_ENV['DB_NAME'] ?? 'inframind';
$dbUser = $_ENV['DB_USER'] ?? '';
$dbPassword = $_ENV['DB_PASSWORD'] ?? '';

// Include Adminer after config is loaded
include __DIR__ . "/adminer-original.php";

function adminer_object() {
    $driver = $_ENV['DB_DRIVER'] ?? 'sqlite';
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '';
    $dbName = $_ENV['DB_NAME'] ?? 'inframind';
    $dbUser = $_ENV['DB_USER'] ?? '';
    $dbPassword = $_ENV['DB_PASSWORD'] ?? '';

    class AdminerAutoLogin extends Adminer {
        public function __construct(
            private string $driver,
            private string $host,
            private string $port,
            private string $dbName,
            private string $dbUser,
            private string $dbPassword,
        ) {}

        function credentials() {
            if ($this->driver === 'sqlite') {
                return array('', '', '');
            }

            $server = $this->host;
            if ($this->port !== '') {
                $separator = $this->driver === 'sqlsrv' ? ',' : ':';
                $server = $server . $separator . $this->port;
            }

            return array($server, $this->dbUser, $this->dbPassword);
        }
        
        function database() {
            if ($this->driver === 'sqlite') {
                return realpath(__DIR__ . '/../database.sqlite');
            }

            return $this->dbName;
        }
        
        function login($login, $password) {
            return true;
        }
    }
    
    return new AdminerAutoLogin($driver, $host, $port, $dbName, $dbUser, $dbPassword);
}
