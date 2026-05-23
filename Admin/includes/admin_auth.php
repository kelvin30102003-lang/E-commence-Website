<?php

declare(strict_types=1);

require_once __DIR__ . '/../../DB/railway_mysql.php';

const ADMIN_SESSION_KEY = 'luvshop_admin_auth';
const ADMIN_CSRF_KEY = 'luvshop_admin_csrf';
const ADMIN_SCHEMA_CACHE_TTL_SECONDS = 43200;

function admin_db(): PDO
{
    return railway_mysql_db();
}

function admin_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);

    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => $isSecure,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

function admin_ensure_tables(PDO $pdo): void
{
    static $ensuredInRequest = false;

    if ($ensuredInRequest) {
        return;
    }
    $ensuredInRequest = true;

    if (admin_can_skip_schema_bootstrap()) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS admin_accounts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(255) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            last_login_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_admin_accounts_email (email),
            KEY idx_admin_accounts_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS admin_activity_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            admin_id BIGINT UNSIGNED NULL,
            action VARCHAR(120) NOT NULL,
            details TEXT NULL,
            ip_address VARCHAR(45) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_admin_activity_logs_admin (admin_id),
            KEY idx_admin_activity_logs_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    admin_ensure_mysql_performance_indexes($pdo);
    admin_mark_schema_bootstrap_checked();
}

function admin_bootstrap_csrf_token(): string
{
    admin_start_session();

    if (empty($_SESSION[ADMIN_CSRF_KEY])) {
        $_SESSION[ADMIN_CSRF_KEY] = bin2hex(random_bytes(32));
    }

    return (string)$_SESSION[ADMIN_CSRF_KEY];
}

function admin_validate_csrf_token(string $token): bool
{
    admin_start_session();
    $sessionToken = (string)($_SESSION[ADMIN_CSRF_KEY] ?? '');

    return $sessionToken !== '' && hash_equals($sessionToken, $token);
}

function admin_count_accounts(PDO $pdo): int
{
    return (int)$pdo->query('SELECT COUNT(*) FROM admin_accounts')->fetchColumn();
}

function admin_create_account(PDO $pdo, string $name, string $email, string $password): array
{
    $name = trim($name);
    $email = strtolower(trim($email));

    if ($name === '' || $email === '') {
        throw new InvalidArgumentException('Name and email are required.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Please enter a valid email address.');
    }

    if (strlen($password) < 8) {
        throw new InvalidArgumentException('Password must be at least 8 characters.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        throw new RuntimeException('Failed to secure password.');
    }

    $statement = $pdo->prepare(
        'INSERT INTO admin_accounts (name, email, password_hash) VALUES (:name, :email, :password_hash)'
    );

    $statement->execute([
        ':name' => $name,
        ':email' => $email,
        ':password_hash' => $passwordHash,
    ]);

    $id = (int)$pdo->lastInsertId();

    $admin = admin_find_account_by_id($pdo, $id);
    if ($admin === null) {
        throw new RuntimeException('Created admin could not be loaded.');
    }

    return $admin;
}

function admin_find_account_by_email(PDO $pdo, string $email): ?array
{
    $statement = $pdo->prepare(
        'SELECT id, name, email, password_hash, is_active, last_login_at FROM admin_accounts WHERE email = :email LIMIT 1'
    );
    $statement->execute([':email' => strtolower(trim($email))]);

    $row = $statement->fetch();

    return is_array($row) ? $row : null;
}

function admin_find_account_by_id(PDO $pdo, int $id): ?array
{
    $statement = $pdo->prepare(
        'SELECT id, name, email, password_hash, is_active, last_login_at FROM admin_accounts WHERE id = :id LIMIT 1'
    );
    $statement->execute([':id' => $id]);

    $row = $statement->fetch();

    return is_array($row) ? $row : null;
}

function admin_record_login(PDO $pdo, int $adminId): void
{
    $statement = $pdo->prepare('UPDATE admin_accounts SET last_login_at = NOW() WHERE id = :id');
    $statement->execute([':id' => $adminId]);
}

function admin_login(array $admin): void
{
    admin_start_session();
    session_regenerate_id(true);

    $_SESSION[ADMIN_SESSION_KEY] = [
        'id' => (int)$admin['id'],
        'name' => (string)$admin['name'],
        'email' => (string)$admin['email'],
        'logged_in_at' => gmdate('c'),
    ];
}

function admin_current(): ?array
{
    admin_start_session();

    if (!isset($_SESSION[ADMIN_SESSION_KEY]) || !is_array($_SESSION[ADMIN_SESSION_KEY])) {
        return null;
    }

    $current = $_SESSION[ADMIN_SESSION_KEY];

    return [
        'id' => (int)($current['id'] ?? 0),
        'name' => (string)($current['name'] ?? ''),
        'email' => (string)($current['email'] ?? ''),
        'logged_in_at' => (string)($current['logged_in_at'] ?? ''),
    ];
}

function admin_require_auth(string $redirect = 'adminLogin.php'): array
{
    $admin = admin_current();

    if ($admin !== null && $admin['id'] > 0) {
        return $admin;
    }

    header('Location: ' . $redirect);
    exit;
}

function admin_logout(): void
{
    admin_start_session();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 3600,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            (bool)($params['secure'] ?? false),
            (bool)($params['httponly'] ?? true)
        );
    }

    session_destroy();
}

function admin_log_activity(PDO $pdo, ?int $adminId, string $action, ?string $details = null): void
{
    $statement = $pdo->prepare(
        'INSERT INTO admin_activity_logs (admin_id, action, details, ip_address) VALUES (:admin_id, :action, :details, :ip_address)'
    );

    $ipAddress = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    if ($ipAddress === '') {
        $ipAddress = null;
    }

    $statement->execute([
        ':admin_id' => $adminId,
        ':action' => trim($action),
        ':details' => $details,
        ':ip_address' => $ipAddress,
    ]);
}

function admin_fetch_dashboard_metrics(PDO $pdo): array
{
    $metrics = [
        'total_sales' => 0.0,
        'today_sales' => 0.0,
        'total_orders' => 0,
        'pending_orders' => 0,
        'total_customers' => 0,
        'low_stock_variants' => 0,
        'active_products' => 0,
    ];

    if (admin_table_exists($pdo, 'orders')) {
        $salesSql = "
            SELECT
                COALESCE(SUM(CASE
                    WHEN payment_status IN ('paid', 'partial_refund')
                    AND order_status NOT IN ('cancelled', 'refunded')
                    THEN total_amount ELSE 0 END), 0) AS total_sales,
                COALESCE(SUM(CASE
                    WHEN payment_status IN ('paid', 'partial_refund')
                    AND order_status NOT IN ('cancelled', 'refunded')
                    AND DATE(COALESCE(placed_at, created_at)) = CURDATE()
                    THEN total_amount ELSE 0 END), 0) AS today_sales,
                COUNT(*) AS total_orders,
                SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) AS pending_orders
            FROM orders
        ";

        $row = $pdo->query($salesSql)->fetch();
        if (is_array($row)) {
            $metrics['total_sales'] = (float)($row['total_sales'] ?? 0);
            $metrics['today_sales'] = (float)($row['today_sales'] ?? 0);
            $metrics['total_orders'] = (int)($row['total_orders'] ?? 0);
            $metrics['pending_orders'] = (int)($row['pending_orders'] ?? 0);
        }
    }

    if (admin_table_exists($pdo, 'users')) {
        $metrics['total_customers'] = (int)$pdo
            ->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")
            ->fetchColumn();
    }

    if (admin_table_exists($pdo, 'product_variants')) {
        $metrics['low_stock_variants'] = (int)$pdo
            ->query('SELECT COUNT(*) FROM product_variants WHERE is_active = 1 AND stock_quantity <= low_stock_threshold')
            ->fetchColumn();
    }

    if (admin_table_exists($pdo, 'products')) {
        $metrics['active_products'] = (int)$pdo
            ->query("SELECT COUNT(*) FROM products WHERE status = 'active'")
            ->fetchColumn();
    }

    return $metrics;
}

function admin_fetch_recent_orders(PDO $pdo, int $limit = 8): array
{
    if (!admin_table_exists($pdo, 'orders')) {
        return [];
    }

    $limit = max(1, min($limit, 50));

    $usersJoin = admin_table_exists($pdo, 'users')
        ? "LEFT JOIN users u ON u.id = o.user_id"
        : '';

    $customerSelect = admin_table_exists($pdo, 'users')
        ? "COALESCE(NULLIF(u.name, ''), NULLIF(u.email, ''), 'Guest')"
        : "'Guest'";

    $sql = "
        SELECT
            o.order_number,
            o.total_amount,
            o.order_status,
            o.payment_status,
            COALESCE(o.placed_at, o.created_at) AS order_date,
            {$customerSelect} AS customer_name
        FROM orders o
        {$usersJoin}
        ORDER BY COALESCE(o.placed_at, o.created_at) DESC
        LIMIT {$limit}
    ";

    $rows = $pdo->query($sql)->fetchAll();

    return is_array($rows) ? $rows : [];
}

function admin_fetch_order_status_summary(PDO $pdo): array
{
    $summary = [
        'completed' => 0,
        'pending' => 0,
        'cancelled' => 0,
        'total' => 0,
    ];

    if (!admin_table_exists($pdo, 'orders')) {
        return $summary;
    }

    $sql = "
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN order_status IN ('confirmed', 'delivered') THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN order_status IN ('pending', 'processing', 'shipped') THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN order_status IN ('cancelled', 'refunded') THEN 1 ELSE 0 END) AS cancelled
        FROM orders
    ";

    $row = $pdo->query($sql)->fetch();
    if (!is_array($row)) {
        return $summary;
    }

    $summary['total'] = (int)($row['total'] ?? 0);
    $summary['completed'] = (int)($row['completed'] ?? 0);
    $summary['pending'] = (int)($row['pending'] ?? 0);
    $summary['cancelled'] = (int)($row['cancelled'] ?? 0);

    return $summary;
}

function admin_fetch_revenue_trend(PDO $pdo, int $weeks = 4): array
{
    $weeks = max(2, min($weeks, 12));

    $result = [
        'labels' => [],
        'values' => [],
        'max' => 0.0,
        'has_data' => false,
    ];

    $today = new DateTimeImmutable('today');
    $ranges = [];

    for ($i = $weeks - 1; $i >= 0; $i--) {
        $daysFrom = ($i * 7) + 6;
        $daysTo = $i * 7;

        $start = $today->modify('-' . $daysFrom . ' days');
        $end = $today->modify('-' . $daysTo . ' days');

        $ranges[] = [
            'start' => $start,
            'end' => $end,
            'label' => 'Week ' . (string)($weeks - $i),
            'revenue' => 0.0,
        ];
    }

    if (admin_table_exists($pdo, 'orders')) {
        $minDate = $ranges[0]['start']->format('Y-m-d');
        $maxDate = $ranges[count($ranges) - 1]['end']->format('Y-m-d');

        $statement = $pdo->prepare(
            "SELECT
                DATE(COALESCE(placed_at, created_at)) AS order_date,
                SUM(total_amount) AS revenue
             FROM orders
             WHERE DATE(COALESCE(placed_at, created_at)) BETWEEN :min_date AND :max_date
               AND payment_status IN ('paid', 'partial_refund')
               AND order_status NOT IN ('cancelled', 'refunded')
             GROUP BY DATE(COALESCE(placed_at, created_at))"
        );
        $statement->execute([
            ':min_date' => $minDate,
            ':max_date' => $maxDate,
        ]);

        $rows = $statement->fetchAll();
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $orderDateRaw = (string)($row['order_date'] ?? '');
                if ($orderDateRaw === '') {
                    continue;
                }

                $orderDate = DateTimeImmutable::createFromFormat('Y-m-d', $orderDateRaw);
                if (!$orderDate instanceof DateTimeImmutable) {
                    continue;
                }

                $dayRevenue = (float)($row['revenue'] ?? 0);

                foreach ($ranges as $index => $range) {
                    if ($orderDate >= $range['start'] && $orderDate <= $range['end']) {
                        $ranges[$index]['revenue'] += $dayRevenue;
                        break;
                    }
                }
            }
        }
    }

    foreach ($ranges as $range) {
        $value = max(0.0, (float)$range['revenue']);
        $result['labels'][] = $range['label'];
        $result['values'][] = $value;
        $result['max'] = max($result['max'], $value);
    }

    $result['has_data'] = $result['max'] > 0;

    return $result;
}

function admin_fetch_activity_logs(PDO $pdo, int $limit = 100): array
{
    if (!admin_table_exists($pdo, 'admin_activity_logs')) {
        return [];
    }

    $limit = max(1, min($limit, 500));

    $sql = "
        SELECT
            l.id,
            l.action,
            l.details,
            l.ip_address,
            l.created_at,
            a.name AS admin_name,
            a.email AS admin_email
        FROM admin_activity_logs l
        LEFT JOIN admin_accounts a ON a.id = l.admin_id
        ORDER BY l.created_at DESC, l.id DESC
        LIMIT {$limit}
    ";

    $rows = $pdo->query($sql)->fetchAll();

    return is_array($rows) ? $rows : [];
}

function admin_table_exists(PDO $pdo, string $tableName): bool
{
    static $cache = [];

    if (array_key_exists($tableName, $cache)) {
        return $cache[$tableName];
    }

    $statement = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name'
    );
    $statement->execute([':table_name' => $tableName]);

    $exists = ((int)$statement->fetchColumn()) > 0;
    $cache[$tableName] = $exists;

    return $exists;
}

function admin_ensure_mysql_performance_indexes(PDO $pdo): void
{
    static $ensured = false;

    if ($ensured) {
        return;
    }
    $ensured = true;

    $definitions = [
        [
            'table' => 'products',
            'name' => 'idx_products_updated_at_id',
            'columns' => ['updated_at', 'id'],
            'sql' => 'CREATE INDEX idx_products_updated_at_id ON `products` (`updated_at`, `id`)',
        ],
        [
            'table' => 'products',
            'name' => 'idx_products_status_updated_at_id',
            'columns' => ['status', 'updated_at', 'id'],
            'sql' => 'CREATE INDEX idx_products_status_updated_at_id ON `products` (`status`, `updated_at`, `id`)',
        ],
        [
            'table' => 'products',
            'name' => 'idx_products_category_updated_at_id',
            'columns' => ['category_id', 'updated_at', 'id'],
            'sql' => 'CREATE INDEX idx_products_category_updated_at_id ON `products` (`category_id`, `updated_at`, `id`)',
        ],
        [
            'table' => 'product_variants',
            'name' => 'idx_product_variants_product_id',
            'columns' => ['product_id'],
            'sql' => 'CREATE INDEX idx_product_variants_product_id ON `product_variants` (`product_id`)',
        ],
        [
            'table' => 'product_variants',
            'name' => 'idx_product_variants_product_active_stock',
            'columns' => ['product_id', 'is_active', 'stock_quantity', 'low_stock_threshold'],
            'sql' => 'CREATE INDEX idx_product_variants_product_active_stock ON `product_variants` (`product_id`, `is_active`, `stock_quantity`, `low_stock_threshold`)',
        ],
        [
            'table' => 'product_variants',
            'name' => 'idx_product_variants_product_price',
            'columns' => ['product_id', 'price'],
            'sql' => 'CREATE INDEX idx_product_variants_product_price ON `product_variants` (`product_id`, `price`)',
        ],
        [
            'table' => 'product_images',
            'name' => 'idx_product_images_product_id',
            'columns' => ['product_id'],
            'sql' => 'CREATE INDEX idx_product_images_product_id ON `product_images` (`product_id`)',
        ],
        [
            'table' => 'orders',
            'name' => 'idx_orders_status_created_at',
            'columns' => ['order_status', 'created_at'],
            'sql' => 'CREATE INDEX idx_orders_status_created_at ON `orders` (`order_status`, `created_at`)',
        ],
        [
            'table' => 'orders',
            'name' => 'idx_orders_created_at',
            'columns' => ['created_at'],
            'sql' => 'CREATE INDEX idx_orders_created_at ON `orders` (`created_at`)',
        ],
        [
            'table' => 'order_items',
            'name' => 'idx_order_items_product_id',
            'columns' => ['product_id'],
            'sql' => 'CREATE INDEX idx_order_items_product_id ON `order_items` (`product_id`)',
        ],
    ];

    try {
        $targetTables = array_values(array_unique(array_map(
            static fn (array $definition): string => (string)$definition['table'],
            $definitions
        )));

        $indexMetadata = admin_fetch_table_index_metadata($pdo, $targetTables);
        $tableColumnsCache = [];

        foreach ($definitions as $definition) {
            $tableName = (string)$definition['table'];
            $indexName = (string)$definition['name'];
            $columns = $definition['columns'];
            $signature = implode(',', $columns);

            if (!admin_table_exists($pdo, $tableName)) {
                continue;
            }

            if (isset($indexMetadata[$tableName]['by_name'][$indexName])) {
                continue;
            }

            if (isset($indexMetadata[$tableName]['by_signature'][$signature])) {
                continue;
            }

            if (!admin_table_has_columns($pdo, $tableName, $columns, $tableColumnsCache)) {
                continue;
            }

            try {
                $pdo->exec((string)$definition['sql']);
                $indexMetadata[$tableName]['by_name'][$indexName] = true;
                $indexMetadata[$tableName]['by_signature'][$signature] = true;
            } catch (Throwable) {
                // Non-fatal: index creation can fail if DB permissions are restricted.
            }
        }
    } catch (Throwable) {
        // Non-fatal: skip automatic index setup if metadata lookup is unavailable.
    }
}

function admin_fetch_table_index_metadata(PDO $pdo, array $tableNames): array
{
    $metadata = [];
    if (count($tableNames) === 0) {
        return $metadata;
    }

    $quotedTables = [];
    foreach ($tableNames as $tableName) {
        $quotedTables[] = $pdo->quote((string)$tableName);
    }
    $inList = implode(', ', $quotedTables);

    $sql = "
        SELECT
            table_name AS table_name_key,
            index_name AS index_name_key,
            seq_in_index AS seq_in_index_key,
            column_name AS column_name_key
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name IN ({$inList})
        ORDER BY table_name, index_name, seq_in_index
    ";
    $rows = $pdo->query($sql)->fetchAll();
    if (!is_array($rows)) {
        return $metadata;
    }

    $columnsByIndex = [];
    foreach ($rows as $row) {
        $tableName = (string)($row['table_name_key'] ?? '');
        $indexName = (string)($row['index_name_key'] ?? '');
        $columnName = (string)($row['column_name_key'] ?? '');

        if ($tableName === '' || $indexName === '' || $columnName === '') {
            continue;
        }

        if (!isset($columnsByIndex[$tableName])) {
            $columnsByIndex[$tableName] = [];
        }
        if (!isset($columnsByIndex[$tableName][$indexName])) {
            $columnsByIndex[$tableName][$indexName] = [];
        }
        $columnsByIndex[$tableName][$indexName][] = $columnName;
    }

    foreach ($columnsByIndex as $tableName => $indexes) {
        $metadata[$tableName] = [
            'by_name' => [],
            'by_signature' => [],
        ];

        foreach ($indexes as $indexName => $columns) {
            $metadata[$tableName]['by_name'][(string)$indexName] = true;
            $metadata[$tableName]['by_signature'][implode(',', $columns)] = true;
        }
    }

    return $metadata;
}

function admin_table_has_columns(PDO $pdo, string $tableName, array $requiredColumns, array &$cache): bool
{
    if (!isset($cache[$tableName])) {
        $statement = $pdo->prepare(
            'SELECT column_name AS column_name_key FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table_name'
        );
        $statement->execute([':table_name' => $tableName]);
        $rows = $statement->fetchAll();

        $cache[$tableName] = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $column = (string)($row['column_name_key'] ?? '');
                if ($column !== '') {
                    $cache[$tableName][$column] = true;
                }
            }
        }
    }

    foreach ($requiredColumns as $column) {
        if (!isset($cache[$tableName][(string)$column])) {
            return false;
        }
    }

    return true;
}

function admin_schema_bootstrap_cache_file(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . '.admin_schema_bootstrap.cache';
}

function admin_can_skip_schema_bootstrap(): bool
{
    $cacheFile = admin_schema_bootstrap_cache_file();
    if (!is_file($cacheFile)) {
        return false;
    }

    $lastCheckedAt = (int)@filemtime($cacheFile);
    if ($lastCheckedAt <= 0) {
        return false;
    }

    return (time() - $lastCheckedAt) < ADMIN_SCHEMA_CACHE_TTL_SECONDS;
}

function admin_mark_schema_bootstrap_checked(): void
{
    $cacheFile = admin_schema_bootstrap_cache_file();
    @touch($cacheFile);
}

function admin_html(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function admin_format_money(float $amount): string
{
    return '$' . number_format($amount, 2);
}

function admin_status_badge_class(string $status): string
{
    $normalized = strtolower(trim($status));

    if (in_array($normalized, ['delivered', 'completed', 'paid', 'confirmed'], true)) {
        return 'bg-green-100 text-green-700';
    }

    if (in_array($normalized, ['pending', 'processing', 'unpaid', 'preparing'], true)) {
        return 'bg-yellow-100 text-yellow-700';
    }

    if (in_array($normalized, ['cancelled', 'failed', 'refunded', 'returned'], true)) {
        return 'bg-red-100 text-red-700';
    }

    return 'bg-slate-100 text-slate-700';
}
