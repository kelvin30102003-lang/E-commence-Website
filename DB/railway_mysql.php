<?php

function railway_mysql_bool(mixed $value, bool $default = false): bool
{
    if (is_bool($value)) {
        return $value;
    }
    if (is_int($value) || is_float($value)) {
        return (int)$value !== 0;
    }

    $normalized = strtolower(trim((string)$value));
    if ($normalized === '') {
        return $default;
    }

    if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
        return true;
    }
    if (in_array($normalized, ['0', 'false', 'off', 'no'], true)) {
        return false;
    }

    return $default;
}

function railway_mysql_config(): array
{
    $configPath = __DIR__ . '/railway_mysql_config.php';

    if (file_exists($configPath)) {
        $config = require $configPath;

        if (!is_array($config)) {
            throw new RuntimeException('DB/railway_mysql_config.php must return an array.');
        }

        return [
            'host' => (string)($config['host'] ?? ''),
            'port' => (string)($config['port'] ?? ''),
            'database' => (string)($config['database'] ?? ''),
            'user' => (string)($config['user'] ?? ''),
            'password' => (string)($config['password'] ?? ''),
            'charset' => (string)($config['charset'] ?? 'utf8mb4'),
            'persistent' => railway_mysql_bool($config['persistent'] ?? true, true),
        ];
    }

    $host = getenv('MYSQLHOST') ?: '';
    $port = getenv('MYSQLPORT') ?: '';
    $database = getenv('MYSQLDATABASE') ?: '';
    $user = getenv('MYSQLUSER') ?: '';
    $password = getenv('MYSQLPASSWORD') ?: '';

    $mysqlUrl = getenv('MYSQL_URL') ?: '';
    if ($mysqlUrl !== '') {
        $parsed = parse_url($mysqlUrl);

        if (is_array($parsed)) {
            $host = $host !== '' ? $host : (string)($parsed['host'] ?? '');
            $port = $port !== '' ? $port : (string)($parsed['port'] ?? '');
            $database = $database !== '' ? $database : ltrim((string)($parsed['path'] ?? ''), '/');
            $user = $user !== '' ? $user : (string)($parsed['user'] ?? '');
            $password = $password !== '' ? $password : (string)($parsed['pass'] ?? '');
        }
    }

    return [
        'host' => $host,
        'port' => $port,
        'database' => $database,
        'user' => $user,
        'password' => $password,
        'charset' => 'utf8mb4',
        'persistent' => railway_mysql_bool(getenv('MYSQL_PERSISTENT') ?: '1', true),
    ];
}

function railway_mysql_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = railway_mysql_config();
    $requiredKeys = ['host', 'port', 'database', 'user', 'password'];

    foreach ($requiredKeys as $key) {
        if ($config[$key] === '') {
            throw new RuntimeException(
                'Missing MySQL config "' . $key . '". Set MYSQL* env vars or create DB/railway_mysql_config.php.'
            );
        }
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );

    $pdo = new PDO(
        $dsn,
        $config['user'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => railway_mysql_bool($config['persistent'] ?? true, true),
        ]
    );

    return $pdo;
}
