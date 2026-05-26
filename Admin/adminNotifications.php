<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_layout.php';

admin_start_session();
$admin = admin_require_auth('adminLogin.php');

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = admin_db();
    admin_ensure_tables($pdo);

    $notifications = admin_build_notifications($pdo);
    echo json_encode([
        'ok' => true,
        'total' => (int)$notifications['total'],
        'items' => $notifications['items'],
        'admin_id' => (int)$admin['id'],
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => $exception->getMessage(),
        'total' => 0,
        'items' => [],
    ], JSON_UNESCAPED_SLASHES);
}

function admin_build_notifications(PDO $pdo): array
{
    $items = [];
    $total = 0;

    if (admin_table_exists($pdo, 'contact_messages')) {
        $total += (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();
        $rows = $pdo->query(
            "SELECT id, full_name, email, subject, created_at
             FROM contact_messages
             WHERE status = 'new'
             ORDER BY created_at DESC, id DESC
             LIMIT 5"
        )->fetchAll();
        foreach (is_array($rows) ? $rows : [] as $row) {
            $items[] = [
                'type' => 'message',
                'icon' => 'mail',
                'title' => 'New message: ' . admin_notification_text((string)($row['subject'] ?? 'Contact message')),
                'detail' => admin_notification_text((string)($row['full_name'] ?? 'Customer')) . ' - ' . admin_notification_text((string)($row['email'] ?? '')),
                'href' => 'manageMessages.php?status=new&contact_id=' . (int)($row['id'] ?? 0),
                'time' => admin_notification_time((string)($row['created_at'] ?? '')),
                'sort_at' => (string)($row['created_at'] ?? ''),
            ];
        }
    }

    if (admin_table_exists($pdo, 'payment_slips')) {
        $total += (int)$pdo->query("SELECT COUNT(*) FROM payment_slips WHERE status = 'pending'")->fetchColumn();
        $ordersJoin = admin_table_exists($pdo, 'orders')
            ? 'LEFT JOIN orders o ON o.id = ps.order_id'
            : '';
        $orderNumberExpression = admin_table_exists($pdo, 'orders')
            ? 'o.order_number'
            : "''";
        $methodsJoin = admin_table_exists($pdo, 'payment_methods')
            ? 'LEFT JOIN payment_methods pm ON pm.id = ps.payment_method_id'
            : '';
        $methodNameExpression = admin_table_exists($pdo, 'payment_methods')
            ? 'pm.name'
            : "'payment method'";
        $rows = $pdo->query(
            "SELECT ps.id, ps.amount, ps.transaction_id, ps.created_at, {$orderNumberExpression} AS order_number, {$methodNameExpression} AS method_name
             FROM payment_slips ps
             {$ordersJoin}
             {$methodsJoin}
             WHERE ps.status = 'pending'
             ORDER BY ps.created_at DESC, ps.id DESC
             LIMIT 5"
        )->fetchAll();
        foreach (is_array($rows) ? $rows : [] as $row) {
            $orderNumber = trim((string)($row['order_number'] ?? ''));
            $items[] = [
                'type' => 'payment_slip',
                'icon' => 'receipt_long',
                'title' => 'Payment slip waiting review',
                'detail' => trim(($orderNumber !== '' ? 'Order #' . $orderNumber . ' - ' : '') . admin_notification_money((float)($row['amount'] ?? 0)) . ' via ' . (string)($row['method_name'] ?? 'payment method')),
                'href' => 'managePaymentSlips.php?status=pending&view_id=' . (int)($row['id'] ?? 0),
                'time' => admin_notification_time((string)($row['created_at'] ?? '')),
                'sort_at' => (string)($row['created_at'] ?? ''),
            ];
        }
    }

    if (admin_table_exists($pdo, 'orders')) {
        $total += (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();
        $rows = $pdo->query(
            "SELECT id, order_number, total_amount, payment_status, created_at, placed_at
             FROM orders
             WHERE order_status = 'pending'
             ORDER BY COALESCE(placed_at, created_at) DESC, id DESC
             LIMIT 5"
        )->fetchAll();
        foreach (is_array($rows) ? $rows : [] as $row) {
            $createdAt = (string)($row['placed_at'] ?? $row['created_at'] ?? '');
            $items[] = [
                'type' => 'order',
                'icon' => 'shopping_cart',
                'title' => 'New pending order #' . admin_notification_text((string)($row['order_number'] ?? $row['id'] ?? '')),
                'detail' => admin_notification_money((float)($row['total_amount'] ?? 0)) . ' - ' . admin_notification_text((string)($row['payment_status'] ?? 'unpaid')),
                'href' => 'manageOrders.php?status=pending&view_id=' . (int)($row['id'] ?? 0),
                'time' => admin_notification_time($createdAt),
                'sort_at' => $createdAt,
            ];
        }
    }

    usort($items, static function (array $a, array $b): int {
        return strcmp((string)($b['sort_at'] ?? ''), (string)($a['sort_at'] ?? ''));
    });

    $items = array_slice(array_map(static function (array $item): array {
        unset($item['sort_at']);
        return $item;
    }, $items), 0, 10);

    return ['total' => $total, 'items' => $items];
}

function admin_notification_text(string $value): string
{
    return trim(strip_tags($value));
}

function admin_notification_money(float $amount): string
{
    return number_format($amount, 2) . ' MMK';
}

function admin_notification_time(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    try {
        return (new DateTimeImmutable($value))->format('M d, h:i A');
    } catch (Throwable) {
        return $value;
    }
}
