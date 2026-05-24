<?php

declare(strict_types=1);

function railway_redis_config(): array
{
    $configPath = __DIR__ . '/redis_config.php';

    if (file_exists($configPath)) {
        $config = require $configPath;

        if (!is_array($config)) {
            throw new RuntimeException('DB/redis_config.php must return an array.');
        }

        return [
            'enabled' => (bool)($config['enabled'] ?? true),
            'host' => trim((string)($config['host'] ?? '')),
            'port' => max(1, (int)($config['port'] ?? 6379)),
            'database' => max(0, (int)($config['database'] ?? 0)),
            'username' => trim((string)($config['username'] ?? '')),
            'password' => (string)($config['password'] ?? ''),
            'timeout' => max(0.1, (float)($config['timeout'] ?? 1.5)),
            'read_timeout' => max(0.1, (float)($config['read_timeout'] ?? 1.5)),
        ];
    }

    $enabledRaw = strtolower(trim((string)(getenv('REDIS_CACHE_ENABLED') ?: '1')));
    $enabled = !in_array($enabledRaw, ['0', 'false', 'off', 'no'], true);

    $host = trim((string)(getenv('REDIS_HOST') ?: getenv('REDISHOST') ?: ''));
    $port = (int)(getenv('REDIS_PORT') ?: getenv('REDISPORT') ?: 6379);
    $database = (int)(getenv('REDIS_DB') ?: getenv('REDISDATABASE') ?: 0);
    $username = trim((string)(getenv('REDIS_USERNAME') ?: getenv('REDISUSER') ?: ''));
    $password = (string)(getenv('REDIS_PASSWORD') ?: getenv('REDISPASSWORD') ?: '');

    $redisUrl = trim((string)(getenv('REDIS_URL') ?: ''));
    if ($redisUrl !== '') {
        $parsed = parse_url($redisUrl);
        if (is_array($parsed)) {
            $host = $host !== '' ? $host : trim((string)($parsed['host'] ?? ''));
            if (!empty($parsed['port'])) {
                $port = (int)$parsed['port'];
            }
            $path = trim((string)($parsed['path'] ?? ''));
            if ($path !== '' && $path !== '/') {
                $database = max(0, (int)ltrim($path, '/'));
            }
            if (!empty($parsed['user'])) {
                $username = $username !== '' ? $username : trim((string)$parsed['user']);
            }
            if (!empty($parsed['pass'])) {
                $password = $password !== '' ? $password : (string)$parsed['pass'];
            }
        }
    }

    return [
        'enabled' => $enabled,
        'host' => $host,
        'port' => max(1, $port),
        'database' => max(0, $database),
        'username' => $username,
        'password' => $password,
        'timeout' => max(0.1, (float)(getenv('REDIS_CONNECT_TIMEOUT') ?: 1.5)),
        'read_timeout' => max(0.1, (float)(getenv('REDIS_READ_TIMEOUT') ?: 1.5)),
    ];
}

function railway_redis_is_enabled(): bool
{
    $config = railway_redis_config();
    return (bool)($config['enabled'] ?? false);
}

function railway_redis_client(): ?Redis
{
    static $client = null;
    static $attempted = false;

    if ($attempted) {
        return $client;
    }
    $attempted = true;

    if (!railway_redis_is_enabled() || !class_exists('Redis')) {
        return null;
    }

    $config = railway_redis_config();
    $host = trim((string)($config['host'] ?? ''));
    if ($host === '') {
        return null;
    }

    $port = max(1, (int)($config['port'] ?? 6379));
    $timeout = max(0.1, (float)($config['timeout'] ?? 1.5));
    $readTimeout = max(0.1, (float)($config['read_timeout'] ?? 1.5));
    $password = (string)($config['password'] ?? '');
    $username = trim((string)($config['username'] ?? ''));
    $database = max(0, (int)($config['database'] ?? 0));

    try {
        $redis = new Redis();
        $connected = $redis->connect($host, $port, $timeout);
        if ($connected !== true) {
            return null;
        }

        if ($password !== '' || $username !== '') {
            $authPayload = $username !== '' ? [$username, $password] : $password;
            $authed = $redis->auth($authPayload);
            if ($authed !== true) {
                return null;
            }
        }

        if ($database > 0) {
            $selected = $redis->select($database);
            if ($selected !== true) {
                return null;
            }
        }

        $redis->setOption(Redis::OPT_READ_TIMEOUT, $readTimeout);
        $client = $redis;
    } catch (Throwable) {
        $client = null;
    }

    return $client;
}
