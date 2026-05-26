<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/../Users/includes/shop_backend.php';

admin_start_session();
$admin = admin_require_auth('adminLogin.php');

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = admin_db();
    shop_ensure_payment_tables($pdo);

    $status = trim((string)($_GET['status'] ?? ''));
    $methodId = max(0, (int)($_GET['payment_method_id'] ?? 0));
    $date = trim((string)($_GET['date'] ?? ''));

    $where = ['1 = 1'];
    $params = [];
    if ($status !== '') {
        $where[] = 'status = :status';
        $params[':status'] = $status;
    }
    if ($methodId > 0) {
        $where[] = 'payment_method_id = :payment_method_id';
        $params[':payment_method_id'] = $methodId;
    }
    if ($date !== '') {
        $where[] = 'DATE(created_at) = :created_date';
        $params[':created_date'] = $date;
    }

    $statement = $pdo->prepare(
        "SELECT
            COUNT(*) AS total_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
            COALESCE(MAX(id), 0) AS latest_id
         FROM payment_slips
         WHERE " . implode(' AND ', $where)
    );
    $statement->execute($params);
    $row = $statement->fetch();

    echo json_encode([
        'ok' => true,
        'total_count' => (int)($row['total_count'] ?? 0),
        'pending_count' => (int)($row['pending_count'] ?? 0),
        'latest_id' => (int)($row['latest_id'] ?? 0),
        'checked_at' => time(),
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => $exception->getMessage(),
    ]);
}
