<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin_auth.php';

admin_start_session();
$currentAdmin = admin_current();

try {
    $pdo = admin_db();
    admin_ensure_tables($pdo);

    if ($currentAdmin !== null && (int)$currentAdmin['id'] > 0) {
        admin_log_activity($pdo, (int)$currentAdmin['id'], 'admin.logout', 'Admin logged out.');
    }
} catch (Throwable $exception) {
    // Ignore DB errors on logout path.
}

admin_logout();
header('Location: adminLogin.php');
exit;
