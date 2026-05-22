<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

$rawBody = file_get_contents('php://input');
$payload = json_decode((string)$rawBody, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid JSON body']);
    exit;
}

$idToken = trim((string)($payload['idToken'] ?? ''));

if ($idToken === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Missing idToken']);
    exit;
}

$firebaseConfigPath = __DIR__ . '/firebase_config.php';
if (!file_exists($firebaseConfigPath)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Missing Firebase config']);
    exit;
}

$firebaseConfig = require $firebaseConfigPath;
$apiKey = is_array($firebaseConfig) ? (string)($firebaseConfig['apiKey'] ?? '') : '';

if ($apiKey === '') {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Missing Firebase API key']);
    exit;
}

require_once __DIR__ . '/../DB/railway_mysql.php';

try {
    $firebaseUser = lookupFirebaseUserByIdToken($apiKey, $idToken);
    $pdo = railway_mysql_db();

    ensureUsersTable($pdo);
    upsertUsersTable($pdo, $firebaseUser);

    ensureFirebaseUsersTable($pdo);
    upsertFirebaseUser($pdo, $firebaseUser);

    echo json_encode([
        'ok' => true,
        'uid' => $firebaseUser['uid'],
        'email' => $firebaseUser['email'],
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => $exception->getMessage(),
    ]);
}

function lookupFirebaseUserByIdToken(string $apiKey, string $idToken): array
{
    $url = 'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . rawurlencode($apiKey);
    $requestBody = json_encode(['idToken' => $idToken], JSON_UNESCAPED_SLASHES);

    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('Could not initialize Firebase verification request.');
        }

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $requestBody,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($curl);
        $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($response === false) {
            throw new RuntimeException('Firebase verification failed: ' . $curlError);
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $requestBody,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        $httpCode = 0;

        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $headerLine) {
                if (preg_match('/^HTTP\/\S+\s+(\d{3})/i', $headerLine, $matches) === 1) {
                    $httpCode = (int)$matches[1];
                    break;
                }
            }
        }

        if ($response === false) {
            throw new RuntimeException('Firebase verification failed (HTTP stream).');
        }
    }

    $responseJson = json_decode($response, true);

    if ($httpCode !== 200 || !is_array($responseJson) || !isset($responseJson['users'][0])) {
        throw new RuntimeException('Invalid Firebase token or user lookup failed.');
    }

    $user = $responseJson['users'][0];

    $uid = (string)($user['localId'] ?? '');
    if ($uid === '') {
        throw new RuntimeException('Firebase user ID is missing.');
    }

    $providerId = null;
    if (isset($user['providerUserInfo']) && is_array($user['providerUserInfo']) && isset($user['providerUserInfo'][0])) {
        $providerId = (string)($user['providerUserInfo'][0]['providerId'] ?? '');
        if ($providerId === '') {
            $providerId = null;
        }
    }

    $lastLoginAt = null;
    if (isset($user['lastLoginAt']) && is_numeric($user['lastLoginAt'])) {
        $lastLoginAt = gmdate('Y-m-d H:i:s', (int)floor(((int)$user['lastLoginAt']) / 1000));
    }

    return [
        'uid' => $uid,
        'email' => (string)($user['email'] ?? ''),
        'display_name' => (string)($user['displayName'] ?? ''),
        'provider_id' => $providerId,
        'photo_url' => (string)($user['photoUrl'] ?? ''),
        'email_verified' => !empty($user['emailVerified']) ? 1 : 0,
        'last_login_at' => $lastLoginAt,
    ];
}

function ensureFirebaseUsersTable(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS firebase_users (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            firebase_uid VARCHAR(128) NOT NULL UNIQUE,
            email VARCHAR(255) NULL,
            display_name VARCHAR(255) NULL,
            provider_id VARCHAR(64) NULL,
            photo_url TEXT NULL,
            email_verified TINYINT(1) NOT NULL DEFAULT 0,
            last_sign_in_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function ensureUsersTable(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            firebase_uid VARCHAR(128) NOT NULL UNIQUE,
            name VARCHAR(150) NULL,
            email VARCHAR(255) NULL UNIQUE,
            phone VARCHAR(30) NULL,
            avatar VARCHAR(255) NULL,
            role ENUM('customer','admin','staff') NOT NULL DEFAULT 'customer',
            email_verified TINYINT(1) NOT NULL DEFAULT 0,
            last_login_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function upsertUsersTable(PDO $pdo, array $firebaseUser): void
{
    $statement = $pdo->prepare(
        'INSERT INTO users (firebase_uid, name, email, avatar, email_verified, last_login_at)
         VALUES (:firebase_uid, :name, :email, :avatar, :email_verified, :last_login_at)
         ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            email = VALUES(email),
            avatar = VALUES(avatar),
            email_verified = VALUES(email_verified),
            last_login_at = VALUES(last_login_at)'
    );

    $statement->execute([
        ':firebase_uid' => $firebaseUser['uid'],
        ':name' => $firebaseUser['display_name'] !== '' ? $firebaseUser['display_name'] : null,
        ':email' => $firebaseUser['email'] !== '' ? $firebaseUser['email'] : null,
        ':avatar' => $firebaseUser['photo_url'] !== '' ? $firebaseUser['photo_url'] : null,
        ':email_verified' => $firebaseUser['email_verified'],
        ':last_login_at' => $firebaseUser['last_login_at'],
    ]);
}

function upsertFirebaseUser(PDO $pdo, array $firebaseUser): void
{
    $statement = $pdo->prepare(
        'INSERT INTO firebase_users (firebase_uid, email, display_name, provider_id, photo_url, email_verified, last_sign_in_at)
         VALUES (:firebase_uid, :email, :display_name, :provider_id, :photo_url, :email_verified, :last_sign_in_at)
         ON DUPLICATE KEY UPDATE
            email = VALUES(email),
            display_name = VALUES(display_name),
            provider_id = VALUES(provider_id),
            photo_url = VALUES(photo_url),
            email_verified = VALUES(email_verified),
            last_sign_in_at = VALUES(last_sign_in_at)'
    );

    $statement->execute([
        ':firebase_uid' => $firebaseUser['uid'],
        ':email' => $firebaseUser['email'] !== '' ? $firebaseUser['email'] : null,
        ':display_name' => $firebaseUser['display_name'] !== '' ? $firebaseUser['display_name'] : null,
        ':provider_id' => $firebaseUser['provider_id'],
        ':photo_url' => $firebaseUser['photo_url'] !== '' ? $firebaseUser['photo_url'] : null,
        ':email_verified' => $firebaseUser['email_verified'],
        ':last_sign_in_at' => $firebaseUser['last_login_at'],
    ]);
}
