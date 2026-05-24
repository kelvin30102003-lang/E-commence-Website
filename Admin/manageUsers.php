<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_layout.php';

admin_start_session();
$admin = admin_require_auth('adminLogin.php');
admin_page_cache_start($admin, 'users', ADMIN_PAGE_CACHE_TTL_SECONDS);

$pdo = admin_db();
admin_ensure_tables($pdo);
ensureUserManagementColumns($pdo);

$csrfToken = admin_bootstrap_csrf_token();

$queryFilters = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
    'page' => max(1, (int)($_GET['page'] ?? 1)),
    'per_page' => max(5, min(100, (int)($_GET['per_page'] ?? 10))),
];

if (!in_array($queryFilters['status'], ['', 'active', 'blocked'], true)) {
    $queryFilters['status'] = '';
}

handleUserManagementPostActions($pdo, $admin);

$flash = pullUserManagementFlash();
$stats = fetchUserManagementStats($pdo);

$listData = fetchStoreUsers($pdo, $queryFilters);
$storeUsers = $listData['rows'];
$totalRows = (int)$listData['total'];
$totalPages = max(1, (int)ceil($totalRows / $queryFilters['per_page']));
$currentPage = min($queryFilters['page'], $totalPages);

if ($currentPage !== $queryFilters['page']) {
    $queryFilters['page'] = $currentPage;
    $listData = fetchStoreUsers($pdo, $queryFilters);
    $storeUsers = $listData['rows'];
}

$baseQuery = buildUserManagementFilterQuery($queryFilters);

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>User Management | LuvShop Admin</title>
    <?php admin_render_critical_css(); ?>
    <?php $adminCssHref = admin_css_href(); ?>
<?php if ($adminCssHref !== null): ?>
    <link href="<?= admin_html($adminCssHref) ?>" rel="stylesheet"/>
<?php endif; ?>
    <link href="<?= admin_html(admin_material_symbols_href()) ?>" rel="stylesheet"/>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .soft-shadow {
            box-shadow: 0 10px 30px -5px rgba(120, 85, 94, 0.08);
        }
    </style>
</head>
<body class="bg-surface text-on-surface">
<?php admin_render_sidebar($admin, 'users'); ?>

<main class="ml-64 min-h-screen" id="app-main">
    <?php
    admin_render_header($admin, [
        'search_action' => 'manageUsers.php',
        'search_method' => 'get',
        'search_name' => 'q',
        'search_value' => trim((string)$queryFilters['q']),
        'search_placeholder' => 'Search users...',
        'search_hidden' => [
            'status' => (string)$queryFilters['status'],
            'per_page' => (string)$queryFilters['per_page'],
            'page' => '1',
        ],
    ]);
    ?>

    <div class="p-6 space-y-6 max-w-[1280px] mx-auto">
        <?php if ($flash !== null): ?>
            <div class="rounded-xl px-4 py-3 border <?= $flash['type'] === 'error' ? 'bg-error-container border-red-200 text-on-error-container' : 'bg-secondary-container border-green-200 text-on-secondary-container' ?>">
                <?= admin_html($flash['message']) ?>
            </div>
        <?php endif; ?>

        <section class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-primary">User Management</h2>
                <p class="text-on-surface-variant mt-1">Simple user control: view, search, and block/unblock accounts.</p>
            </div>
            <a class="px-4 py-2 rounded-full bg-white border border-outline-variant text-on-surface hover:bg-surface-container-low" href="manageUsers.php<?= admin_html($baseQuery) ?>">
                Refresh
            </a>
        </section>

        <section class="bg-surface-container-lowest rounded-xl soft-shadow border border-surface-container p-4 flex flex-wrap items-center gap-3">
            <form class="flex items-center gap-3 flex-wrap w-full" method="get">
                <input class="flex-1 min-w-[260px] rounded-full bg-surface border-none" name="q" placeholder="Search by name, email, or ID..." type="text" value="<?= admin_html($queryFilters['q']) ?>"/>
                <select class="rounded-lg bg-surface border-none" name="status">
                    <option value="" <?= $queryFilters['status'] === '' ? 'selected' : '' ?>>All Status</option>
                    <option value="active" <?= $queryFilters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="blocked" <?= $queryFilters['status'] === 'blocked' ? 'selected' : '' ?>>Blocked</option>
                </select>
                <select class="rounded-lg bg-surface border-none" name="per_page">
                    <?php foreach ([10, 20, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= (int)$queryFilters['per_page'] === $size ? 'selected' : '' ?>><?= $size ?>/page</option>
                    <?php endforeach; ?>
                </select>
                <input name="page" type="hidden" value="1"/>
                <button class="px-4 py-2 bg-primary text-on-primary rounded-lg font-semibold" type="submit">Apply</button>
                <a class="px-4 py-2 rounded-lg border border-outline-variant hover:bg-surface-container-low" href="manageUsers.php">Clear</a>
            </form>
        </section>

        <section class="bg-surface-container-lowest rounded-xl soft-shadow overflow-hidden border border-surface-container">
            <div class="px-6 py-4 border-b border-surface-container bg-surface-container-low">
                <h3 class="text-lg font-semibold text-primary">Users</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[900px]">
                    <thead class="bg-surface-container-low border-b border-surface-container">
                    <tr>
                        <th class="p-4 text-sm text-on-surface-variant uppercase tracking-wider">User</th>
                        <th class="p-4 text-sm text-on-surface-variant uppercase tracking-wider">Orders</th>
                        <th class="p-4 text-sm text-on-surface-variant uppercase tracking-wider">Total Spent</th>
                        <th class="p-4 text-sm text-on-surface-variant uppercase tracking-wider">Joined</th>
                        <th class="p-4 text-sm text-on-surface-variant uppercase tracking-wider">Status</th>
                        <th class="p-4 text-sm text-on-surface-variant uppercase tracking-wider text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container">
                    <?php if (count($storeUsers) === 0): ?>
                        <tr>
                            <td class="p-6 text-on-surface-variant" colspan="6">No users found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($storeUsers as $user): ?>
                        <?php
                        $isActive = (int)($user['is_active'] ?? 1) === 1;
                        $avatar = trim((string)($user['avatar'] ?? ''));
                        ?>
                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-2xl overflow-hidden shadow-sm bg-primary-container flex items-center justify-center text-primary font-semibold">
                                        <?php if ($avatar !== ''): ?>
                                            <img alt="<?= admin_html((string)$user['name']) ?>" class="w-full h-full object-cover" decoding="async" fetchpriority="low" height="48" loading="lazy" src="<?= admin_html($avatar) ?>" width="48"/>
                                        <?php else: ?>
                                            <?= admin_html(initialsLabel((string)$user['name'])) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-primary"><?= admin_html((string)$user['name']) ?></p>
                                        <p class="text-sm text-on-surface-variant"><?= admin_html((string)$user['email']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <span class="px-3 py-1 rounded-full bg-surface-container text-sm"><?= number_format((int)$user['orders_count']) ?> orders</span>
                            </td>
                            <td class="p-4 font-semibold"><?= admin_html(admin_format_money((float)$user['total_spent'])) ?></td>
                            <td class="p-4 text-sm text-on-surface-variant"><?= admin_html(formatDateTimeLabel((string)$user['created_at'])) ?></td>
                            <td class="p-4">
                                <span class="<?= $isActive ? 'px-3 py-1 rounded-full text-xs font-semibold bg-secondary-container text-on-secondary-container' : 'px-3 py-1 rounded-full text-xs font-semibold bg-error-container text-on-error-container' ?>">
                                    <?= $isActive ? 'Active' : 'Blocked' ?>
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a class="p-2 bg-primary-container/40 text-primary rounded-lg hover:bg-primary-container transition-colors" href="manageOrders.php?q=<?= urlencode((string)$user['email']) ?>" title="Order History">
                                        <span class="material-symbols-outlined text-[20px]">history</span>
                                    </a>
                                    <form method="post">
                                        <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
                                        <input name="action" type="hidden" value="toggle_user_block"/>
                                        <input name="user_id" type="hidden" value="<?= (int)$user['id'] ?>"/>
                                        <input name="is_active" type="hidden" value="<?= $isActive ? '0' : '1' ?>"/>
                                        <input name="return_query" type="hidden" value="<?= admin_html($baseQuery) ?>"/>
                                        <button class="px-3 py-2 rounded-lg text-sm font-semibold <?= $isActive ? 'bg-error-container text-on-error-container hover:opacity-90' : 'bg-secondary-container text-on-secondary-container hover:opacity-90' ?>" type="submit">
                                            <?= $isActive ? 'Block User' : 'Unblock User' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 bg-surface-container-low flex justify-between items-center border-t border-surface-container">
                <p class="text-sm text-on-surface-variant">
                    Showing <?= $totalRows === 0 ? 0 : (($currentPage - 1) * $queryFilters['per_page'] + 1) ?>-<?= min($currentPage * $queryFilters['per_page'], $totalRows) ?>
                    of <?= number_format($totalRows) ?> users
                </p>
                <div class="flex items-center gap-2">
                    <?php if ($currentPage > 1): ?>
                        <a class="w-10 h-10 flex items-center justify-center rounded-lg bg-white border border-outline-variant hover:bg-surface-container-low transition-colors" href="manageUsers.php<?= admin_html(buildUserManagementFilterQuery(array_merge($queryFilters, ['page' => $currentPage - 1]))) ?>">
                            <span class="material-symbols-outlined">chevron_left</span>
                        </a>
                    <?php else: ?>
                        <button class="w-10 h-10 flex items-center justify-center rounded-lg bg-white border border-outline-variant opacity-40" disabled>
                            <span class="material-symbols-outlined">chevron_left</span>
                        </button>
                    <?php endif; ?>

                    <?php
                    $pageStart = max(1, $currentPage - 1);
                    $pageEnd = min($totalPages, $pageStart + 2);
                    $pageStart = max(1, $pageEnd - 2);
                    for ($page = $pageStart; $page <= $pageEnd; $page++):
                        ?>
                        <a class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant transition-colors font-semibold <?= $page === $currentPage ? 'bg-primary text-on-primary' : 'bg-white hover:bg-surface-container-low' ?>" href="manageUsers.php<?= admin_html(buildUserManagementFilterQuery(array_merge($queryFilters, ['page' => $page]))) ?>">
                            <?= $page ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a class="w-10 h-10 flex items-center justify-center rounded-lg bg-white border border-outline-variant hover:bg-surface-container-low transition-colors" href="manageUsers.php<?= admin_html(buildUserManagementFilterQuery(array_merge($queryFilters, ['page' => $currentPage + 1]))) ?>">
                            <span class="material-symbols-outlined">chevron_right</span>
                        </a>
                    <?php else: ?>
                        <button class="w-10 h-10 flex items-center justify-center rounded-lg bg-white border border-outline-variant opacity-40" disabled>
                            <span class="material-symbols-outlined">chevron_right</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-surface-container-lowest p-4 rounded-lg soft-shadow border border-surface-container flex flex-col gap-1">
                <span class="text-xs text-on-surface-variant uppercase tracking-wider">New This Month</span>
                <span class="text-2xl font-semibold">+<?= number_format((int)$stats['new_this_month']) ?></span>
                <span class="text-sm text-on-surface-variant">Recently created user accounts.</span>
            </div>
            <div class="bg-surface-container-lowest p-4 rounded-lg soft-shadow border border-surface-container flex flex-col gap-1">
                <span class="text-xs text-on-surface-variant uppercase tracking-wider">Repeat Order Rate</span>
                <span class="text-2xl font-semibold"><?= number_format((float)$stats['repeat_rate'], 1) ?>%</span>
                <span class="text-sm text-on-surface-variant">Users who ordered more than once.</span>
            </div>
            <div class="bg-surface-container-lowest p-4 rounded-lg soft-shadow border border-surface-container flex flex-col gap-1">
                <span class="text-xs text-on-surface-variant uppercase tracking-wider">Avg Lifetime Value</span>
                <span class="text-2xl font-semibold"><?= admin_html(admin_format_money((float)$stats['avg_lifetime_value'])) ?></span>
                <span class="text-sm text-on-surface-variant">Average paid order value per user.</span>
            </div>
        </section>
    </div>
</main>

</body>
</html>
<?php admin_page_cache_finish(); ?>

<?php

function ensureUserManagementColumns(PDO $pdo): void
{
    if (admin_table_exists($pdo, 'admin_accounts')) {
        ensureTableColumn($pdo, 'admin_accounts', 'role', "VARCHAR(50) NOT NULL DEFAULT 'admin' AFTER email");
        ensureTableColumn($pdo, 'admin_accounts', 'can_manage_products', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER role');
        ensureTableColumn($pdo, 'admin_accounts', 'can_manage_orders', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER can_manage_products');
        ensureTableColumn($pdo, 'admin_accounts', 'can_manage_users', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER can_manage_orders');
        ensureTableColumn($pdo, 'admin_accounts', 'can_manage_coupons', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER can_manage_users');
        ensureTableColumn($pdo, 'admin_accounts', 'can_manage_reports', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER can_manage_coupons');
    }

    if (admin_table_exists($pdo, 'users')) {
        ensureTableColumn($pdo, 'users', 'avatar', 'VARCHAR(255) NULL AFTER email');
        ensureTableColumn($pdo, 'users', 'role', "VARCHAR(50) NOT NULL DEFAULT 'customer' AFTER avatar");
        ensureTableColumn($pdo, 'users', 'is_active', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER role');
        ensureTableColumn($pdo, 'users', 'can_manage_products', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active');
        ensureTableColumn($pdo, 'users', 'can_manage_orders', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER can_manage_products');
        ensureTableColumn($pdo, 'users', 'can_manage_users', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER can_manage_orders');
        ensureTableColumn($pdo, 'users', 'can_manage_coupons', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER can_manage_users');
        ensureTableColumn($pdo, 'users', 'can_manage_reports', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER can_manage_coupons');
    }
}

function ensureTableColumn(PDO $pdo, string $table, string $column, string $definition): void
{
    if (tableColumnExists($pdo, $table, $column)) {
        return;
    }

    $sql = sprintf('ALTER TABLE `%s` ADD COLUMN `%s` %s', $table, $column, $definition);
    $pdo->exec($sql);
}

function tableColumnExists(PDO $pdo, string $table, string $column): bool
{
    static $columnCache = [];

    return admin_table_has_columns($pdo, $table, [$column], $columnCache);
}

function handleUserManagementPostActions(PDO $pdo, array $currentAdmin): void
{
    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        return;
    }

    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    if (!admin_validate_csrf_token($csrfToken)) {
        setUserManagementFlash('error', 'Invalid form token. Please refresh and try again.');
        redirectManageUsers((string)($_POST['return_query'] ?? ''));
    }

    $action = trim((string)($_POST['action'] ?? ''));

    try {
        switch ($action) {
            case 'toggle_user_block':
                $userId = toggleStoreUserBlock($pdo);
                setUserManagementFlash('success', 'User status updated.');
                admin_log_activity($pdo, (int)$currentAdmin['id'], 'user.block', 'Updated user block status #' . $userId);
                redirectManageUsers((string)($_POST['return_query'] ?? ''));
                break;

            default:
                setUserManagementFlash('error', 'Unknown action.');
                redirectManageUsers((string)($_POST['return_query'] ?? ''));
        }
    } catch (Throwable $exception) {
        setUserManagementFlash('error', $exception->getMessage());
        redirectManageUsers((string)($_POST['return_query'] ?? ''));
    }
}

function toggleStoreUserBlock(PDO $pdo): int
{
    if (!admin_table_exists($pdo, 'users')) {
        throw new RuntimeException('Users table does not exist.');
    }

    $userId = max(0, (int)($_POST['user_id'] ?? 0));
    if ($userId <= 0) {
        throw new InvalidArgumentException('Invalid user account.');
    }

    $existing = fetchStoreUserById($pdo, $userId);
    if ($existing === null) {
        throw new RuntimeException('User not found.');
    }

    $isActive = (int)($_POST['is_active'] ?? 1) === 1 ? 1 : 0;

    $stmt = $pdo->prepare(
        'UPDATE users
         SET is_active = :is_active,
             updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute([
        ':is_active' => $isActive,
        ':id' => $userId,
    ]);

    return $userId;
}

function fetchUserManagementStats(PDO $pdo): array
{
    $cacheKey = admin_cache_key('users_stats', ['v' => 1]);
    $cached = null;
    if (admin_cache_fetch($cacheKey, $cached) && is_array($cached)) {
        return array_merge([
            'new_this_month' => 0,
            'repeat_rate' => 0.0,
            'avg_lifetime_value' => 0.0,
        ], $cached);
    }

    $stats = [
        'new_this_month' => 0,
        'repeat_rate' => 0.0,
        'avg_lifetime_value' => 0.0,
    ];

    if (!admin_table_exists($pdo, 'users')) {
        return $stats;
    }

    $row = $pdo->query(
        "SELECT
            SUM(CASE WHEN created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 ELSE 0 END) AS new_this_month
         FROM users"
    )->fetch();
    if (is_array($row)) {
        $stats['new_this_month'] = (int)($row['new_this_month'] ?? 0);
    }

    if (!admin_table_exists($pdo, 'orders')) {
        return $stats;
    }

    $summary = $pdo->query(
        "SELECT
            COUNT(DISTINCT u.id) AS total_users,
            SUM(CASE WHEN COALESCE(order_data.order_count, 0) > 1 THEN 1 ELSE 0 END) AS repeat_users,
            AVG(COALESCE(order_data.total_spent, 0)) AS avg_lifetime
         FROM users u
         LEFT JOIN (
            SELECT
                user_id,
                COUNT(*) AS order_count,
                SUM(CASE
                    WHEN payment_status IN ('paid', 'partial_refund')
                    AND order_status NOT IN ('cancelled', 'refunded')
                    THEN total_amount ELSE 0 END) AS total_spent
            FROM orders
            GROUP BY user_id
         ) order_data ON order_data.user_id = u.id"
    )->fetch();

    if (is_array($summary)) {
        $totalUsers = (int)($summary['total_users'] ?? 0);
        $repeatUsers = (int)($summary['repeat_users'] ?? 0);
        $stats['repeat_rate'] = $totalUsers > 0 ? ($repeatUsers / $totalUsers) * 100 : 0.0;
        $stats['avg_lifetime_value'] = (float)($summary['avg_lifetime'] ?? 0.0);
    }

    admin_cache_store($cacheKey, $stats, ADMIN_PAGE_CACHE_TTL_SECONDS);
    return $stats;
}

function fetchStoreUsers(PDO $pdo, array $filters): array
{
    $cacheKey = admin_cache_key('users_list', [
        'filters' => $filters,
        'v' => 1,
    ]);
    $cached = null;
    if (admin_cache_fetch($cacheKey, $cached) && is_array($cached)) {
        $cachedRows = $cached['rows'] ?? null;
        $cachedTotal = $cached['total'] ?? null;
        if (is_array($cachedRows)) {
            return ['rows' => $cachedRows, 'total' => (int)$cachedTotal];
        }
    }

    if (!admin_table_exists($pdo, 'users')) {
        return ['rows' => [], 'total' => 0];
    }

    $ordersTableExists = admin_table_exists($pdo, 'orders');
    $params = [];
    $where = ['1=1'];

    if ($filters['q'] !== '') {
        $where[] = '(u.name LIKE :q OR u.email LIKE :q OR CAST(u.id AS CHAR) LIKE :q)';
        $params[':q'] = '%' . $filters['q'] . '%';
    }

    if ($filters['status'] === 'active') {
        $where[] = 'u.is_active = 1';
    } elseif ($filters['status'] === 'blocked') {
        $where[] = 'u.is_active = 0';
    }

    $whereSql = implode(' AND ', $where);

    $countSql = "SELECT COUNT(*) FROM users u WHERE {$whereSql}";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $offset = max(0, ((int)$filters['page'] - 1) * (int)$filters['per_page']);

    $orderJoinSql = $ordersTableExists
        ? "LEFT JOIN (
            SELECT
                user_id,
                COUNT(*) AS orders_count,
                SUM(CASE
                    WHEN payment_status IN ('paid', 'partial_refund')
                    AND order_status NOT IN ('cancelled', 'refunded')
                    THEN total_amount ELSE 0 END) AS total_spent
            FROM orders
            GROUP BY user_id
        ) ord ON ord.user_id = u.id"
        : "LEFT JOIN (
            SELECT NULL AS user_id, 0 AS orders_count, 0 AS total_spent
        ) ord ON 1 = 0";

    $sql = "
        SELECT
            u.id,
            u.name,
            u.email,
            u.avatar,
            u.is_active,
            COALESCE(ord.orders_count, 0) AS orders_count,
            COALESCE(ord.total_spent, 0) AS total_spent,
            u.created_at
        FROM users u
        {$orderJoinSql}
        WHERE {$whereSql}
        ORDER BY u.created_at DESC, u.id DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', (int)$filters['per_page'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();
    $result = ['rows' => is_array($rows) ? $rows : [], 'total' => $total];
    admin_cache_store($cacheKey, $result, 15);
    return $result;
}

function fetchStoreUserById(PDO $pdo, int $id): ?array
{
    if ($id <= 0 || !admin_table_exists($pdo, 'users')) {
        return null;
    }

    $cacheKey = admin_cache_key('users_detail', [
        'user_id' => $id,
        'v' => 1,
    ]);
    $cached = null;
    if (admin_cache_fetch($cacheKey, $cached)) {
        return is_array($cached) ? $cached : null;
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    $result = is_array($row) ? $row : null;
    admin_cache_store($cacheKey, $result, ADMIN_PAGE_CACHE_TTL_SECONDS);
    return $result;
}

function setUserManagementFlash(string $type, string $message): void
{
    $_SESSION['admin_users_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function pullUserManagementFlash(): ?array
{
    if (!isset($_SESSION['admin_users_flash']) || !is_array($_SESSION['admin_users_flash'])) {
        return null;
    }

    $flash = $_SESSION['admin_users_flash'];
    unset($_SESSION['admin_users_flash']);
    return $flash;
}

function redirectManageUsers(string $returnQuery): never
{
    $safeQuery = sanitizeUserManagementReturnQuery($returnQuery);
    header('Location: manageUsers.php' . $safeQuery);
    exit;
}

function sanitizeUserManagementReturnQuery(string $query): string
{
    $query = trim($query);
    if ($query === '') {
        return '';
    }

    $raw = ltrim($query, '?');
    parse_str($raw, $parsed);
    if (!is_array($parsed)) {
        return '';
    }

    $safe = [
        'q' => trim((string)($parsed['q'] ?? '')),
        'status' => trim((string)($parsed['status'] ?? '')),
        'per_page' => max(5, min(100, (int)($parsed['per_page'] ?? 10))),
        'page' => max(1, (int)($parsed['page'] ?? 1)),
    ];

    if ($safe['status'] !== '' && $safe['status'] !== 'active' && $safe['status'] !== 'blocked') {
        $safe['status'] = '';
    }

    return buildUserManagementFilterQuery($safe);
}

function buildUserManagementFilterQuery(array $filters): string
{
    $query = http_build_query([
        'q' => (string)$filters['q'],
        'status' => (string)$filters['status'],
        'per_page' => (int)$filters['per_page'],
        'page' => (int)$filters['page'],
    ]);
    return $query === '' ? '' : '?' . $query;
}

function initialsLabel(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return 'U';
    }

    $parts = preg_split('/\s+/', $name);
    if (!is_array($parts) || count($parts) === 0) {
        return strtoupper(substr($name, 0, 1));
    }

    $first = strtoupper(substr((string)$parts[0], 0, 1));
    $last = '';
    if (count($parts) > 1) {
        $last = strtoupper(substr((string)$parts[count($parts) - 1], 0, 1));
    }

    return $first . ($last !== '' ? $last : '');
}

function formatDateTimeLabel(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return 'Never';
    }
    try {
        $dt = new DateTimeImmutable($value);
        return $dt->format('M d, Y h:i A');
    } catch (Throwable) {
        return $value;
    }
}



