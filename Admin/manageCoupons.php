<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_layout.php';

admin_start_session();
$admin = admin_require_auth('adminLogin.php');
admin_page_cache_start($admin, 'coupons', ADMIN_PAGE_CACHE_TTL_SECONDS);

$pdo = admin_db();
admin_ensure_tables($pdo);
ensureCouponsTable($pdo);

$csrfToken = admin_bootstrap_csrf_token();
$discountTypeOptions = ['percentage', 'fixed_amount', 'free_shipping'];

$queryFilters = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
    'discount_type' => trim((string)($_GET['discount_type'] ?? '')),
    'per_page' => max(5, min(100, (int)($_GET['per_page'] ?? 10))),
    'page' => max(1, (int)($_GET['page'] ?? 1)),
];

if (!in_array($queryFilters['status'], ['', 'active', 'inactive', 'expired'], true)) {
    $queryFilters['status'] = '';
}
if ($queryFilters['discount_type'] !== '' && !in_array($queryFilters['discount_type'], $discountTypeOptions, true)) {
    $queryFilters['discount_type'] = '';
}

$mode = trim((string)($_GET['mode'] ?? ''));
$editId = max(0, (int)($_GET['edit_id'] ?? 0));

handleCouponPostActions($pdo, $admin, $discountTypeOptions);

$flash = pullCouponFlash();
$stats = fetchCouponStats($pdo);

$listData = fetchCouponList($pdo, $queryFilters);
$coupons = $listData['rows'];
$totalRows = (int)$listData['total'];
$totalPages = max(1, (int)ceil($totalRows / $queryFilters['per_page']));
$currentPage = min($queryFilters['page'], $totalPages);

if ($currentPage !== $queryFilters['page']) {
    $queryFilters['page'] = $currentPage;
    $listData = fetchCouponList($pdo, $queryFilters);
    $coupons = $listData['rows'];
}

$editingCoupon = null;
if ($mode === 'edit' && $editId > 0) {
    $editingCoupon = fetchCouponById($pdo, $editId);
    if ($editingCoupon === null) {
        setCouponFlash('error', 'Coupon not found for editing.');
        redirectManageCoupons(buildCouponFilterQuery($queryFilters));
    }
}

$showForm = ($mode === 'create') || ($mode === 'edit' && $editingCoupon !== null);
$baseQuery = buildCouponFilterQuery($queryFilters);

$startRow = $totalRows === 0 ? 0 : (($queryFilters['page'] - 1) * $queryFilters['per_page']) + 1;
$endRow = $totalRows === 0 ? 0 : min($totalRows, $queryFilters['page'] * $queryFilters['per_page']);

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Coupon Management | LuvShop Admin</title>
    <?php admin_render_critical_css(); ?>
    <?php $adminCssHref = admin_css_href(); ?>
<?php if ($adminCssHref !== null): ?>
    <link href="<?= admin_html($adminCssHref) ?>" rel="stylesheet"/>
<?php endif; ?>
    <link href="<?= admin_html(admin_material_symbols_href()) ?>" rel="stylesheet"/>
    <style>
        body {
            font-family: "Plus Jakarta Sans", sans-serif;
            background: #fbf9f8;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .soft-shadow {
            box-shadow: 0 10px 30px -10px rgba(120, 85, 94, 0.1);
        }
    </style>
</head>
<body class="bg-[#fbf9f8] text-slate-900 min-h-screen">
<?php admin_render_sidebar($admin, 'coupons'); ?>

<main class="ml-64 min-h-screen" id="app-main">
    <?php
    admin_render_header($admin, [
        'search_action' => 'manageCoupons.php',
        'search_method' => 'get',
        'search_name' => 'q',
        'search_value' => trim((string)$queryFilters['q']),
        'search_placeholder' => 'Search by code...',
        'search_hidden' => [
            'status' => (string)$queryFilters['status'],
            'discount_type' => (string)$queryFilters['discount_type'],
            'per_page' => (string)$queryFilters['per_page'],
            'page' => '1',
        ],
    ]);
    ?>

    <section class="p-6 max-w-[1280px] mx-auto space-y-6">
        <?php if ($flash !== null): ?>
            <div class="rounded-xl border px-4 py-3 <?= $flash['type'] === 'error' ? 'bg-red-100 border-red-200 text-red-700' : 'bg-green-100 border-green-200 text-green-700' ?>">
                <?= admin_html($flash['message']) ?>
            </div>
        <?php endif; ?>

        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-[#78555e]">Coupons</h1>
                <p class="text-slate-500 mt-1">Create offers and control discount rules safely.</p>
            </div>
            <div class="flex items-center gap-2">
                <a class="px-4 py-2 rounded-full bg-white border border-slate-300 text-slate-700 hover:bg-slate-50" href="manageCoupons.php<?= admin_html($baseQuery) ?>">
                    Refresh
                </a>
                <a class="px-4 py-2 rounded-full bg-[#78555e] text-white hover:opacity-90" href="manageCoupons.php<?= admin_html($baseQuery) ?>&mode=create">
                    Create Coupon
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl soft-shadow p-5">
                <p class="text-sm text-slate-500">Total Coupons</p>
                <p class="text-3xl font-semibold mt-1"><?= number_format((int)$stats['total']) ?></p>
            </div>
            <div class="bg-white rounded-2xl soft-shadow p-5">
                <p class="text-sm text-slate-500">Active</p>
                <p class="text-3xl font-semibold mt-1 text-emerald-600"><?= number_format((int)$stats['active']) ?></p>
            </div>
            <div class="bg-white rounded-2xl soft-shadow p-5">
                <p class="text-sm text-slate-500">Expired</p>
                <p class="text-3xl font-semibold mt-1 text-amber-600"><?= number_format((int)$stats['expired']) ?></p>
            </div>
            <div class="bg-white rounded-2xl soft-shadow p-5">
                <p class="text-sm text-slate-500">Disabled</p>
                <p class="text-3xl font-semibold mt-1 text-slate-600"><?= number_format((int)$stats['inactive']) ?></p>
            </div>
        </div>

        <form class="bg-white rounded-2xl soft-shadow p-4 flex flex-wrap items-center gap-3" method="get">
            <input name="q" type="hidden" value="<?= admin_html((string)$queryFilters['q']) ?>"/>
            <input name="page" type="hidden" value="1"/>

            <div>
                <label class="block text-xs text-slate-500 mb-1">Status</label>
                <select class="rounded-xl border-slate-300 min-w-[160px]" name="status">
                    <option value="" <?= $queryFilters['status'] === '' ? 'selected' : '' ?>>All</option>
                    <option value="active" <?= $queryFilters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $queryFilters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="expired" <?= $queryFilters['status'] === 'expired' ? 'selected' : '' ?>>Expired</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Discount Type</label>
                <select class="rounded-xl border-slate-300 min-w-[180px]" name="discount_type">
                    <option value="" <?= $queryFilters['discount_type'] === '' ? 'selected' : '' ?>>All Types</option>
                    <?php foreach ($discountTypeOptions as $type): ?>
                        <option value="<?= admin_html($type) ?>" <?= $queryFilters['discount_type'] === $type ? 'selected' : '' ?>><?= admin_html(couponTypeLabel($type)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Rows</label>
                <select class="rounded-xl border-slate-300 min-w-[100px]" name="per_page">
                    <?php foreach ([10, 20, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= (int)$queryFilters['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="mt-5 px-4 py-2 rounded-xl bg-[#78555e] text-white hover:opacity-90" type="submit">
                Apply
            </button>
            <a class="mt-5 px-4 py-2 rounded-xl text-[#78555e] hover:bg-[#ffd1dc]" href="manageCoupons.php">
                Clear
            </a>
        </form>

        <?php if ($showForm): ?>
            <section class="bg-white rounded-2xl soft-shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-[#78555e]"><?= $editingCoupon !== null ? 'Edit Coupon' : 'Create Coupon' ?></h2>
                    <a class="text-slate-500 hover:text-[#78555e]" href="manageCoupons.php<?= admin_html($baseQuery) ?>">Close</a>
                </div>

                <form class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" method="post">
                    <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
                    <input name="action" type="hidden" value="<?= $editingCoupon !== null ? 'update_coupon' : 'create_coupon' ?>"/>
                    <input name="return_query" type="hidden" value="<?= admin_html($baseQuery) ?>"/>
                    <?php if ($editingCoupon !== null): ?>
                        <input name="coupon_id" type="hidden" value="<?= (int)$editingCoupon['id'] ?>"/>
                    <?php endif; ?>

                    <div>
                        <label class="block text-sm font-semibold mb-1">Coupon Code</label>
                        <input class="w-full rounded-xl border-slate-300" maxlength="50" name="code" placeholder="SAVE20" required type="text" value="<?= admin_html((string)($editingCoupon['code'] ?? '')) ?>"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Discount Type</label>
                        <select class="w-full rounded-xl border-slate-300" name="discount_type" required>
                            <?php foreach ($discountTypeOptions as $type): ?>
                                <option value="<?= admin_html($type) ?>" <?= (string)($editingCoupon['discount_type'] ?? '') === $type ? 'selected' : '' ?>><?= admin_html(couponTypeLabel($type)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Discount Value</label>
                        <input class="w-full rounded-xl border-slate-300" min="0" name="discount_value" step="0.01" type="number" value="<?= admin_html(number_format((float)($editingCoupon['discount_value'] ?? 0), 2, '.', '')) ?>"/>
                        <p class="text-xs text-slate-400 mt-1">For free shipping this will be set to 0.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Minimum Order Amount</label>
                        <input class="w-full rounded-xl border-slate-300" min="0" name="min_order_amount" step="0.01" type="number" value="<?= admin_html(number_format((float)($editingCoupon['min_order_amount'] ?? 0), 2, '.', '')) ?>"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Usage Limit</label>
                        <input class="w-full rounded-xl border-slate-300" min="1" name="usage_limit" placeholder="No limit if empty" step="1" type="number" value="<?= admin_html((string)($editingCoupon['usage_limit'] ?? '')) ?>"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Max Discount Amount (optional)</label>
                        <input class="w-full rounded-xl border-slate-300" min="0" name="max_discount_amount" step="0.01" type="number" value="<?= admin_html($editingCoupon !== null && $editingCoupon['max_discount_amount'] !== null ? number_format((float)$editingCoupon['max_discount_amount'], 2, '.', '') : '') ?>"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Start Date</label>
                        <input class="w-full rounded-xl border-slate-300" name="starts_at" type="datetime-local" value="<?= admin_html(formatLocalDateInput((string)($editingCoupon['starts_at'] ?? ''))) ?>"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Expiry Date</label>
                        <input class="w-full rounded-xl border-slate-300" name="expires_at" type="datetime-local" value="<?= admin_html(formatLocalDateInput((string)($editingCoupon['expires_at'] ?? ''))) ?>"/>
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-2 mt-6">
                            <input class="rounded border-slate-300 text-[#78555e]" name="is_active" type="checkbox" value="1" <?= ((int)($editingCoupon['is_active'] ?? 1) === 1) ? 'checked' : '' ?>/>
                            <span class="text-sm font-semibold">Active coupon</span>
                        </label>
                    </div>

                    <div class="md:col-span-2 lg:col-span-3 flex items-center gap-2 pt-2">
                        <button class="px-5 py-2 rounded-xl bg-[#78555e] text-white hover:opacity-90" type="submit">
                            <?= $editingCoupon !== null ? 'Update Coupon' : 'Create Coupon' ?>
                        </button>
                        <a class="px-5 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50" href="manageCoupons.php<?= admin_html($baseQuery) ?>">
                            Cancel
                        </a>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <section class="bg-white rounded-2xl soft-shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px]">
                    <thead class="bg-slate-100 border-b border-slate-200">
                    <tr class="text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3 text-left">Code</th>
                        <th class="px-4 py-3 text-left">Type &amp; Value</th>
                        <th class="px-4 py-3 text-left">Min Order</th>
                        <th class="px-4 py-3 text-left">Usage</th>
                        <th class="px-4 py-3 text-left">Expiry</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                    <?php if (count($coupons) === 0): ?>
                        <tr>
                            <td class="px-4 py-8 text-center text-slate-500" colspan="7">No coupons found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($coupons as $coupon): ?>
                        <?php
                        $couponStatus = couponCurrentStatus((int)$coupon['is_active'], (string)$coupon['expires_at']);
                        $usageText = (int)$coupon['usage_limit'] > 0
                            ? number_format((int)$coupon['used_count']) . ' / ' . number_format((int)$coupon['usage_limit'])
                            : number_format((int)$coupon['used_count']) . ' / Unlimited';
                        ?>
                        <tr>
                            <td class="px-4 py-4 font-semibold text-[#78555e]"><?= admin_html((string)$coupon['code']) ?></td>
                            <td class="px-4 py-4">
                                <div class="font-semibold"><?= admin_html(couponTypeLabel((string)$coupon['discount_type'])) ?></div>
                                <div class="text-sm text-slate-500"><?= admin_html(formatCouponValue((string)$coupon['discount_type'], (float)$coupon['discount_value'])) ?></div>
                            </td>
                            <td class="px-4 py-4"><?= admin_html(admin_format_money((float)$coupon['min_order_amount'])) ?></td>
                            <td class="px-4 py-4 text-slate-600"><?= admin_html($usageText) ?></td>
                            <td class="px-4 py-4 text-slate-600"><?= admin_html(formatDateTimeLabel((string)$coupon['expires_at'])) ?></td>
                            <td class="px-4 py-4">
                                <span class="<?= couponStatusBadgeClass($couponStatus) ?>"><?= admin_html(ucfirst($couponStatus)) ?></span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end items-center gap-2">
                                    <a class="px-3 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm hover:bg-slate-200" href="manageCoupons.php<?= admin_html($baseQuery) ?>&mode=edit&edit_id=<?= (int)$coupon['id'] ?>">
                                        Edit
                                    </a>
                                    <form method="post">
                                        <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
                                        <input name="action" type="hidden" value="toggle_coupon_active"/>
                                        <input name="coupon_id" type="hidden" value="<?= (int)$coupon['id'] ?>"/>
                                        <input name="is_active" type="hidden" value="<?= (int)$coupon['is_active'] === 1 ? '0' : '1' ?>"/>
                                        <input name="return_query" type="hidden" value="<?= admin_html($baseQuery) ?>"/>
                                        <button class="px-3 py-2 rounded-lg text-sm <?= (int)$coupon['is_active'] === 1 ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' ?>" type="submit">
                                            <?= (int)$coupon['is_active'] === 1 ? 'Disable' : 'Enable' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-slate-200 bg-slate-50 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <p class="text-sm text-slate-500">Showing <?= number_format($startRow) ?> to <?= number_format($endRow) ?> of <?= number_format($totalRows) ?> coupons</p>
                <div class="flex items-center gap-2">
                    <?php
                    $prevPage = max(1, $queryFilters['page'] - 1);
                    $nextPage = min($totalPages, $queryFilters['page'] + 1);
                    ?>
                    <a class="w-9 h-9 rounded-full border border-slate-300 flex items-center justify-center <?= $queryFilters['page'] <= 1 ? 'pointer-events-none opacity-40' : 'hover:bg-slate-100' ?>" href="manageCoupons.php<?= admin_html(buildCouponFilterQuery(array_merge($queryFilters, ['page' => $prevPage]))) ?>">
                        <span class="material-symbols-outlined text-sm">chevron_left</span>
                    </a>
                    <?php
                    $pageStart = max(1, $queryFilters['page'] - 2);
                    $pageEnd = min($totalPages, $queryFilters['page'] + 2);
                    for ($page = $pageStart; $page <= $pageEnd; $page++):
                        $isCurrent = $page === (int)$queryFilters['page'];
                        ?>
                        <a class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold <?= $isCurrent ? 'bg-[#78555e] text-white' : 'border border-slate-300 hover:bg-slate-100' ?>" href="manageCoupons.php<?= admin_html(buildCouponFilterQuery(array_merge($queryFilters, ['page' => $page]))) ?>">
                            <?= $page ?>
                        </a>
                    <?php endfor; ?>
                    <a class="w-9 h-9 rounded-full border border-slate-300 flex items-center justify-center <?= $queryFilters['page'] >= $totalPages ? 'pointer-events-none opacity-40' : 'hover:bg-slate-100' ?>" href="manageCoupons.php<?= admin_html(buildCouponFilterQuery(array_merge($queryFilters, ['page' => $nextPage]))) ?>">
                        <span class="material-symbols-outlined text-sm">chevron_right</span>
                    </a>
                </div>
            </div>
        </section>
    </section>
</main>
</body>
</html>
<?php admin_page_cache_finish(); ?>

<?php

function ensureCouponsTable(PDO $pdo): void
{
    if (admin_table_exists($pdo, 'coupons')) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS coupons (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) NOT NULL UNIQUE,
            discount_type ENUM('percentage','fixed_amount','free_shipping') NOT NULL,
            discount_value DECIMAL(12,2) NOT NULL,
            min_order_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            max_discount_amount DECIMAL(12,2) NULL,
            usage_limit INT UNSIGNED NULL,
            used_count INT UNSIGNED NOT NULL DEFAULT 0,
            starts_at TIMESTAMP NULL DEFAULT NULL,
            expires_at TIMESTAMP NULL DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_coupons_code (code),
            KEY idx_coupons_active (is_active),
            KEY idx_coupons_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function handleCouponPostActions(PDO $pdo, array $admin, array $discountTypeOptions): void
{
    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        return;
    }

    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    if (!admin_validate_csrf_token($csrfToken)) {
        setCouponFlash('error', 'Invalid form token. Please refresh and try again.');
        redirectManageCoupons((string)($_POST['return_query'] ?? ''));
    }

    $action = trim((string)($_POST['action'] ?? ''));
    $returnQuery = (string)($_POST['return_query'] ?? '');

    try {
        switch ($action) {
            case 'create_coupon':
                $payload = buildCouponPayloadFromPost($discountTypeOptions);
                $statement = $pdo->prepare(
                    'INSERT INTO coupons
                    (code, discount_type, discount_value, min_order_amount, max_discount_amount, usage_limit, used_count, starts_at, expires_at, is_active, created_at)
                    VALUES
                    (:code, :discount_type, :discount_value, :min_order_amount, :max_discount_amount, :usage_limit, 0, :starts_at, :expires_at, :is_active, NOW())'
                );
                $statement->execute($payload);

                setCouponFlash('success', 'Coupon created.');
                admin_log_activity($pdo, (int)$admin['id'], 'coupon.create', 'Created coupon ' . $payload[':code']);
                redirectManageCoupons($returnQuery);
                break;

            case 'update_coupon':
                $couponId = max(0, (int)($_POST['coupon_id'] ?? 0));
                if ($couponId <= 0) {
                    throw new InvalidArgumentException('Invalid coupon ID.');
                }

                $existing = fetchCouponById($pdo, $couponId);
                if ($existing === null) {
                    throw new RuntimeException('Coupon not found.');
                }

                $payload = buildCouponPayloadFromPost($discountTypeOptions);
                $payload[':id'] = $couponId;

                $statement = $pdo->prepare(
                    'UPDATE coupons
                     SET code = :code,
                         discount_type = :discount_type,
                         discount_value = :discount_value,
                         min_order_amount = :min_order_amount,
                         max_discount_amount = :max_discount_amount,
                         usage_limit = :usage_limit,
                         starts_at = :starts_at,
                         expires_at = :expires_at,
                         is_active = :is_active
                     WHERE id = :id'
                );
                $statement->execute($payload);

                setCouponFlash('success', 'Coupon updated.');
                admin_log_activity($pdo, (int)$admin['id'], 'coupon.update', 'Updated coupon #' . $couponId);
                redirectManageCoupons($returnQuery);
                break;

            case 'toggle_coupon_active':
                $couponId = max(0, (int)($_POST['coupon_id'] ?? 0));
                if ($couponId <= 0) {
                    throw new InvalidArgumentException('Invalid coupon ID.');
                }

                $isActive = (int)($_POST['is_active'] ?? 0) === 1 ? 1 : 0;
                $statement = $pdo->prepare('UPDATE coupons SET is_active = :is_active WHERE id = :id');
                $statement->execute([
                    ':is_active' => $isActive,
                    ':id' => $couponId,
                ]);

                setCouponFlash('success', $isActive === 1 ? 'Coupon enabled.' : 'Coupon disabled.');
                admin_log_activity($pdo, (int)$admin['id'], 'coupon.toggle', 'Coupon #' . $couponId . ' active=' . $isActive);
                redirectManageCoupons($returnQuery);
                break;

            default:
                throw new InvalidArgumentException('Unknown coupon action.');
        }
    } catch (Throwable $exception) {
        $message = $exception->getMessage();
        if ($exception instanceof PDOException && str_contains(strtolower($message), 'duplicate')) {
            $message = 'Coupon code already exists. Please use a different code.';
        }
        setCouponFlash('error', $message);
        redirectManageCoupons($returnQuery);
    }
}

function buildCouponPayloadFromPost(array $discountTypeOptions): array
{
    $code = strtoupper(trim((string)($_POST['code'] ?? '')));
    $code = preg_replace('/\s+/', '', $code) ?? '';
    if ($code === '') {
        throw new InvalidArgumentException('Coupon code is required.');
    }
    if (strlen($code) > 50) {
        throw new InvalidArgumentException('Coupon code must be 50 characters or less.');
    }

    $discountType = trim((string)($_POST['discount_type'] ?? ''));
    if (!in_array($discountType, $discountTypeOptions, true)) {
        throw new InvalidArgumentException('Invalid discount type.');
    }

    $discountValue = parseMoneyInput((string)($_POST['discount_value'] ?? '0'), 'Discount value');
    if ($discountType === 'free_shipping') {
        $discountValue = 0.0;
    } elseif ($discountValue <= 0) {
        throw new InvalidArgumentException('Discount value must be greater than 0.');
    }
    if ($discountType === 'percentage' && $discountValue > 100) {
        throw new InvalidArgumentException('Percentage discount cannot be more than 100%.');
    }

    $minOrderAmount = parseMoneyInput((string)($_POST['min_order_amount'] ?? '0'), 'Minimum order amount');
    $maxDiscountAmount = parseNullableMoneyInput((string)($_POST['max_discount_amount'] ?? ''));

    $usageLimitRaw = trim((string)($_POST['usage_limit'] ?? ''));
    $usageLimit = null;
    if ($usageLimitRaw !== '') {
        if (!ctype_digit($usageLimitRaw) || (int)$usageLimitRaw <= 0) {
            throw new InvalidArgumentException('Usage limit must be a positive whole number.');
        }
        $usageLimit = (int)$usageLimitRaw;
    }

    $startsAt = parseDateTimeInput((string)($_POST['starts_at'] ?? ''));
    $expiresAt = parseDateTimeInput((string)($_POST['expires_at'] ?? ''));
    if ($startsAt !== null && $expiresAt !== null && $expiresAt <= $startsAt) {
        throw new InvalidArgumentException('Expiry date must be later than start date.');
    }

    $isActive = (int)($_POST['is_active'] ?? 0) === 1 ? 1 : 0;

    return [
        ':code' => $code,
        ':discount_type' => $discountType,
        ':discount_value' => $discountValue,
        ':min_order_amount' => $minOrderAmount,
        ':max_discount_amount' => $maxDiscountAmount,
        ':usage_limit' => $usageLimit,
        ':starts_at' => $startsAt,
        ':expires_at' => $expiresAt,
        ':is_active' => $isActive,
    ];
}

function fetchCouponStats(PDO $pdo): array
{
    $cacheKey = admin_cache_key('coupons_stats', ['v' => 1]);
    $cached = null;
    if (admin_cache_fetch($cacheKey, $cached) && is_array($cached)) {
        return array_merge([
            'total' => 0,
            'active' => 0,
            'expired' => 0,
            'inactive' => 0,
        ], $cached);
    }

    $stats = [
        'total' => 0,
        'active' => 0,
        'expired' => 0,
        'inactive' => 0,
    ];

    $sql = "
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN is_active = 1 AND (expires_at IS NULL OR expires_at >= NOW()) THEN 1 ELSE 0 END) AS active,
            SUM(CASE WHEN expires_at IS NOT NULL AND expires_at < NOW() THEN 1 ELSE 0 END) AS expired,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive
        FROM coupons
    ";

    $row = $pdo->query($sql)->fetch();
    if (!is_array($row)) {
        return $stats;
    }

    $stats['total'] = (int)($row['total'] ?? 0);
    $stats['active'] = (int)($row['active'] ?? 0);
    $stats['expired'] = (int)($row['expired'] ?? 0);
    $stats['inactive'] = (int)($row['inactive'] ?? 0);
    admin_cache_store($cacheKey, $stats, ADMIN_PAGE_CACHE_TTL_SECONDS);
    return $stats;
}

function fetchCouponList(PDO $pdo, array $filters): array
{
    $cacheKey = admin_cache_key('coupons_list', [
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

    $params = [];
    $where = ['1=1'];

    if ($filters['q'] !== '') {
        $where[] = 'code LIKE :q';
        $params[':q'] = '%' . $filters['q'] . '%';
    }

    if ($filters['status'] === 'active') {
        $where[] = 'is_active = 1 AND (expires_at IS NULL OR expires_at >= NOW())';
    } elseif ($filters['status'] === 'inactive') {
        $where[] = 'is_active = 0';
    } elseif ($filters['status'] === 'expired') {
        $where[] = 'expires_at IS NOT NULL AND expires_at < NOW()';
    }

    if ($filters['discount_type'] !== '') {
        $where[] = 'discount_type = :discount_type';
        $params[':discount_type'] = $filters['discount_type'];
    }

    $whereSql = implode(' AND ', $where);

    $countSql = "SELECT COUNT(*) FROM coupons WHERE {$whereSql}";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $offset = max(0, ((int)$filters['page'] - 1) * (int)$filters['per_page']);
    $sql = "
        SELECT
            id,
            code,
            discount_type,
            discount_value,
            min_order_amount,
            max_discount_amount,
            usage_limit,
            used_count,
            starts_at,
            expires_at,
            is_active,
            created_at
        FROM coupons
        WHERE {$whereSql}
        ORDER BY created_at DESC, id DESC
        LIMIT :limit OFFSET :offset
    ";

    $statement = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $statement->bindValue($key, $value);
    }
    $statement->bindValue(':limit', (int)$filters['per_page'], PDO::PARAM_INT);
    $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $statement->execute();
    $rows = $statement->fetchAll();

    $result = ['rows' => is_array($rows) ? $rows : [], 'total' => $total];
    admin_cache_store($cacheKey, $result, 15);
    return $result;
}

function fetchCouponById(PDO $pdo, int $couponId): ?array
{
    if ($couponId <= 0) {
        return null;
    }

    $cacheKey = admin_cache_key('coupons_detail', [
        'coupon_id' => $couponId,
        'v' => 1,
    ]);
    $cached = null;
    if (admin_cache_fetch($cacheKey, $cached)) {
        return is_array($cached) ? $cached : null;
    }

    $statement = $pdo->prepare(
        'SELECT
            id,
            code,
            discount_type,
            discount_value,
            min_order_amount,
            max_discount_amount,
            usage_limit,
            used_count,
            starts_at,
            expires_at,
            is_active,
            created_at
         FROM coupons
         WHERE id = :id
         LIMIT 1'
    );
    $statement->execute([':id' => $couponId]);
    $row = $statement->fetch();

    $result = is_array($row) ? $row : null;
    admin_cache_store($cacheKey, $result, ADMIN_PAGE_CACHE_TTL_SECONDS);
    return $result;
}

function parseMoneyInput(string $input, string $label): float
{
    $normalized = trim(str_replace([',', '$', ' '], '', $input));
    if ($normalized === '' || !is_numeric($normalized)) {
        throw new InvalidArgumentException($label . ' must be a valid number.');
    }

    $value = round((float)$normalized, 2);
    if ($value < 0) {
        throw new InvalidArgumentException($label . ' cannot be negative.');
    }

    return $value;
}

function parseNullableMoneyInput(string $input): ?float
{
    $normalized = trim(str_replace([',', '$', ' '], '', $input));
    if ($normalized === '') {
        return null;
    }
    if (!is_numeric($normalized)) {
        throw new InvalidArgumentException('Max discount amount must be a valid number.');
    }

    $value = round((float)$normalized, 2);
    if ($value < 0) {
        throw new InvalidArgumentException('Max discount amount cannot be negative.');
    }

    return $value;
}

function parseDateTimeInput(string $input): ?string
{
    $value = trim($input);
    if ($value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value);
    if (!$date instanceof DateTimeImmutable) {
        throw new InvalidArgumentException('Invalid date/time format.');
    }

    return $date->format('Y-m-d H:i:s');
}

function formatLocalDateInput(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    try {
        $date = new DateTimeImmutable($value);
        return $date->format('Y-m-d\TH:i');
    } catch (Throwable) {
        return '';
    }
}

function couponTypeLabel(string $type): string
{
    return match ($type) {
        'percentage' => 'Percentage',
        'fixed_amount' => 'Fixed Amount',
        'free_shipping' => 'Free Shipping',
        default => ucfirst(str_replace('_', ' ', $type)),
    };
}

function formatCouponValue(string $type, float $value): string
{
    return match ($type) {
        'percentage' => number_format($value, 2) . '%',
        'fixed_amount' => admin_format_money($value),
        'free_shipping' => 'Shipping cost waived',
        default => (string)$value,
    };
}

function couponCurrentStatus(int $isActive, string $expiresAt): string
{
    if ($isActive !== 1) {
        return 'inactive';
    }

    if ($expiresAt !== '') {
        try {
            $expiry = new DateTimeImmutable($expiresAt);
            $now = new DateTimeImmutable('now');
            if ($expiry < $now) {
                return 'expired';
            }
        } catch (Throwable) {
            // Keep active if parsing fails.
        }
    }

    return 'active';
}

function couponStatusBadgeClass(string $status): string
{
    return match ($status) {
        'active' => 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700',
        'expired' => 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700',
        'inactive' => 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-200 text-slate-700',
        default => 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700',
    };
}

function formatDateTimeLabel(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return 'No expiry';
    }

    try {
        $date = new DateTimeImmutable($value);
        return $date->format('M d, Y h:i A');
    } catch (Throwable) {
        return $value;
    }
}

function setCouponFlash(string $type, string $message): void
{
    $_SESSION['admin_coupons_flash'] = ['type' => $type, 'message' => $message];
}

function pullCouponFlash(): ?array
{
    if (!isset($_SESSION['admin_coupons_flash']) || !is_array($_SESSION['admin_coupons_flash'])) {
        return null;
    }

    $flash = $_SESSION['admin_coupons_flash'];
    unset($_SESSION['admin_coupons_flash']);
    return $flash;
}

function redirectManageCoupons(string $returnQuery): never
{
    $safeQuery = sanitizeCouponReturnQuery($returnQuery);
    header('Location: manageCoupons.php' . $safeQuery);
    exit;
}

function sanitizeCouponReturnQuery(string $query): string
{
    $query = trim($query);
    if ($query === '') {
        return '';
    }
    if ($query[0] !== '?') {
        $query = '?' . ltrim($query, '?');
    }
    return $query;
}

function buildCouponFilterQuery(array $filters): string
{
    $query = http_build_query([
        'q' => (string)($filters['q'] ?? ''),
        'status' => (string)($filters['status'] ?? ''),
        'discount_type' => (string)($filters['discount_type'] ?? ''),
        'per_page' => (int)($filters['per_page'] ?? 10),
        'page' => (int)($filters['page'] ?? 1),
    ]);
    return $query === '' ? '' : '?' . $query;
}




