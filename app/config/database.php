<?php

declare(strict_types=1);

/**
 * Membaca kredensial dari environment, lalu dari config.php di akar proyek.
 * Host "localhost" dialihkan ke 127.0.0.1 karena socket bawaan PHP
 * (/run/mysqld/mysqld.sock) tidak cocok dengan socket MariaDB (/tmp/mysql.sock).
 */
function db_settings(): array
{
    static $settings = null;
    if (is_array($settings)) {
        return $settings;
    }

    $host = '127.0.0.1';
    $port = '3306';
    $user = 'app-iot-2026';
    $pass = '';
    $name = 'app-iot-2026';

    $configFile = BASE_PATH . '/config.php';
    if (is_file($configFile)) {
        require $configFile;
    }
    if (isset($db) && is_string($db) && $db !== '') {
        $name = $db;
    }
    if (isset($port) && (is_string($port) || is_int($port))) {
        $port = (string) $port;
    }

    $host = env_string('DB_HOST', (string) $host);
    $port = env_string('DB_PORT', (string) $port);
    $user = env_string('DB_USER', (string) $user);
    $pass = env_string('DB_PASS', (string) $pass);
    $name = env_string('DB_NAME', (string) $name);

    if ($host === 'localhost') {
        $host = '127.0.0.1';
    }

    $settings = [
        'host' => $host,
        'port' => $port,
        'user' => $user,
        'pass' => $pass,
        'name' => $name,
        'socket' => env_string('DB_SOCKET', ''),
    ];

    return $settings;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $settings = db_settings();
    if ($settings['socket'] !== '') {
        $dsn = 'mysql:unix_socket=' . $settings['socket'] . ';dbname=' . $settings['name'] . ';charset=utf8mb4';
    } else {
        $dsn = 'mysql:host=' . $settings['host'] . ';port=' . $settings['port'] . ';dbname=' . $settings['name'] . ';charset=utf8mb4';
    }

    $pdo = new PDO($dsn, $settings['user'], $settings['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_FOUND_ROWS => true,
    ]);
    $pdo->exec("SET time_zone = '+00:00'");

    return $pdo;
}
