<?php

declare(strict_types=1);

function luvshop_load_env_file_once(string $path): void
{
    static $loadedPaths = [];

    if (isset($loadedPaths[$path])) {
        return;
    }

    $loadedPaths[$path] = true;

    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);

        if ($name === '' || getenv($name) !== false) {
            continue;
        }

        $value = trim($value);
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

function luvshop_env_value(string $key): string
{
    $value = getenv($key);

    if ($value === false || $value === '') {
        return '';
    }

    return trim((string)$value);
}

function luvshop_firebase_config_from_env(): array
{
    luvshop_load_env_file_once(__DIR__ . '/../backend/.env');
    luvshop_load_env_file_once(__DIR__ . '/../.env');

    return [
        'apiKey' => luvshop_env_value('FIREBASE_API_KEY'),
        'authDomain' => luvshop_env_value('FIREBASE_AUTH_DOMAIN'),
        'projectId' => luvshop_env_value('FIREBASE_PROJECT_ID'),
        'storageBucket' => luvshop_env_value('FIREBASE_STORAGE_BUCKET'),
        'messagingSenderId' => luvshop_env_value('FIREBASE_MESSAGING_SENDER_ID'),
        'appId' => luvshop_env_value('FIREBASE_APP_ID'),
        'measurementId' => luvshop_env_value('FIREBASE_MEASUREMENT_ID'),
    ];
}

function luvshop_normalize_firebase_config(array $config): array
{
    $normalized = [];

    foreach ($config as $key => $value) {
        $value = trim((string)$value);
        if ($value !== '') {
            $normalized[$key] = $value;
        }
    }

    return $normalized;
}

function luvshop_firebase_config_is_complete(array $config): bool
{
    foreach (['apiKey', 'authDomain', 'projectId', 'storageBucket', 'messagingSenderId', 'appId'] as $requiredKey) {
        if (empty($config[$requiredKey])) {
            return false;
        }
    }

    return true;
}

function luvshop_firebase_config(): array
{
    $localConfigPath = __DIR__ . '/firebase_config.php';
    if (is_file($localConfigPath)) {
        $config = require $localConfigPath;
        if (is_array($config)) {
            $config = luvshop_normalize_firebase_config($config);
            if (luvshop_firebase_config_is_complete($config)) {
                return $config;
            }
        }
    }

    $config = luvshop_normalize_firebase_config(luvshop_firebase_config_from_env());
    if (luvshop_firebase_config_is_complete($config)) {
        return $config;
    }

    $publicConfigPath = __DIR__ . '/firebase_config_public.php';
    if (is_file($publicConfigPath)) {
        $config = require $publicConfigPath;
        if (is_array($config)) {
            $config = luvshop_normalize_firebase_config($config);
            if (luvshop_firebase_config_is_complete($config)) {
                return $config;
            }
        }
    }

    return [];
}
