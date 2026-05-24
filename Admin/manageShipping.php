<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_layout.php';

admin_start_session();
$admin = admin_require_auth('adminLogin.php');
admin_page_cache_start($admin, 'shipping', ADMIN_PAGE_CACHE_TTL_SECONDS);

$pdo = admin_db();
admin_ensure_tables($pdo);

$csrfToken = admin_bootstrap_csrf_token();

$queryFilters = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
    'courier' => trim((string)($_GET['courier'] ?? '')),
    'page' => max(1, (int)($_GET['page'] ?? 1)),
    'per_page' => max(5, min(100, (int)($_GET['per_page'] ?? 10))),
];

if ($queryFilters['status'] !== '' && !in_array($queryFilters['status'], shippingFilterStatusOptions(), true)) {
    $queryFilters['status'] = '';
}

handleShippingPostActions($pdo, $admin, shippingEditableStatusOptions());

$flash = pullShippingFlash();

$ordersTableExists = admin_table_exists($pdo, 'orders');
$shipmentsTableExists = admin_table_exists($pdo, 'shipments');
$usersTableExists = admin_table_exists($pdo, 'users');
$addressesTableExists = admin_table_exists($pdo, 'addresses');

$stats = ($ordersTableExists && $shipmentsTableExists)
    ? fetchShippingStats($pdo)
    : defaultShippingStats();

$courierOptions = $shipmentsTableExists ? fetchCourierOptions($pdo) : [];

$listData = $ordersTableExists
    ? fetchShippingList($pdo, $queryFilters, $shipmentsTableExists, $usersTableExists, $addressesTableExists)
    : ['rows' => [], 'total' => 0];

$shipments = $listData['rows'];
$totalRows = (int)$listData['total'];
$totalPages = max(1, (int)ceil($totalRows / $queryFilters['per_page']));
$currentPage = min($queryFilters['page'], $totalPages);

if ($currentPage !== $queryFilters['page'] && $ordersTableExists) {
    $queryFilters['page'] = $currentPage;
    $listData = fetchShippingList($pdo, $queryFilters, $shipmentsTableExists, $usersTableExists, $addressesTableExists);
    $shipments = $listData['rows'];
}

$baseQuery = buildShippingFilterQuery($queryFilters);
$clearQuery = buildShippingFilterQuery(array_merge($queryFilters, [
    'status' => '',
    'courier' => '',
    'page' => 1,
]));

$startRow = $totalRows === 0 ? 0 : (($queryFilters['page'] - 1) * $queryFilters['per_page']) + 1;
$endRow = $totalRows === 0 ? 0 : min($totalRows, $queryFilters['page'] * $queryFilters['per_page']);

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Shipping Management | LuvShop Admin</title>
    <?php admin_render_critical_css(); ?>
    <?php $adminCssHref = admin_css_href(); ?>
<?php if ($adminCssHref !== null): ?>
    <link href="<?= admin_html($adminCssHref) ?>" rel="stylesheet"/>
<?php endif; ?>
    <link href="<?= admin_html(admin_material_symbols_href()) ?>" rel="stylesheet"/>
<style>
        body {
            background: #fbf9f8;
            font-family: "Plus Jakarta Sans", sans-serif;
            color: #1b1c1c;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .soft-shadow {
            box-shadow: 0 10px 30px -5px rgba(120, 85, 94, 0.08);
        }
        .shipping-row:hover {
            background: rgba(255, 209, 220, 0.14);
        }
    </style>
</head>
<body class="bg-surface text-on-surface">
<?php admin_render_sidebar($admin, 'shipping'); ?>

<main class="ml-64 min-h-screen" id="app-main">
    <?php
    admin_render_header($admin, [
        'search_action' => 'manageShipping.php',
        'search_method' => 'get',
        'search_name' => 'q',
        'search_value' => trim((string)$queryFilters['q']),
        'search_placeholder' => 'Search order, tracking, courier...',
        'search_hidden' => [
            'status' => (string)$queryFilters['status'],
            'courier' => (string)$queryFilters['courier'],
            'per_page' => (string)$queryFilters['per_page'],
            'page' => '1',
        ],
    ]);
    ?>

    <div class="p-6 max-w-[1280px] mx-auto space-y-6">
        <?php if (!$ordersTableExists): ?>
            <div class="rounded-xl border border-red-200 bg-error-container px-6 py-4 text-on-error-container">
                `orders` table is missing. Shipping management needs orders data.
            </div>
        <?php endif; ?>

        <?php if (!$shipmentsTableExists): ?>
            <div class="rounded-xl border border-red-200 bg-error-container px-6 py-4 text-on-error-container">
                `shipments` table is missing. Create it first to assign couriers and tracking.
            </div>
        <?php endif; ?>

        <?php if ($flash !== null): ?>
            <div class="rounded-xl border px-6 py-4 <?= $flash['type'] === 'error' ? 'bg-error-container border-red-200 text-on-error-container' : 'bg-secondary-container border-green-200 text-on-secondary-container' ?>">
                <?= admin_html($flash['message']) ?>
            </div>
        <?php endif; ?>

        <section class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h2 class="font-headline-lg text-3xl text-primary">Shipping Management</h2>
                <p class="text-on-surface-variant mt-1">Assign couriers, manage tracking, and complete deliveries fast.</p>
            </div>
            <div class="flex items-center gap-3">
                <a class="flex items-center gap-2 px-5 py-3 bg-white border border-outline-variant rounded-full text-on-surface hover:bg-surface-container-low transition-colors" href="manageOrders.php">
                    <span class="material-symbols-outlined">receipt_long</span>
                    Open Orders
                </a>
                <a class="flex items-center gap-2 px-5 py-3 bg-primary text-white rounded-full hover:opacity-90 transition-opacity" href="manageShipping.php<?= admin_html($baseQuery) ?>">
                    <span class="material-symbols-outlined">refresh</span>
                    Refresh
                </a>
            </div>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border border-outline-variant rounded-xl p-5 soft-shadow">
                <p class="text-sm text-on-surface-variant">Total Orders</p>
                <h3 class="text-2xl font-semibold mt-1"><?= number_format((int)$stats['total_orders']) ?></h3>
            </div>
            <div class="bg-white border border-outline-variant rounded-xl p-5 soft-shadow">
                <p class="text-sm text-on-surface-variant">In Delivery Flow</p>
                <h3 class="text-2xl font-semibold mt-1"><?= number_format((int)$stats['moving_count']) ?></h3>
            </div>
            <div class="bg-white border border-outline-variant rounded-xl p-5 soft-shadow">
                <p class="text-sm text-on-surface-variant">Delivered</p>
                <h3 class="text-2xl font-semibold mt-1 text-secondary"><?= number_format((int)$stats['delivered_count']) ?></h3>
            </div>
            <div class="bg-white border border-outline-variant rounded-xl p-5 soft-shadow">
                <p class="text-sm text-on-surface-variant">Pending Assignment</p>
                <h3 class="text-2xl font-semibold mt-1 text-[#9a6f00]"><?= number_format((int)$stats['pending_assignment']) ?></h3>
            </div>
        </section>

        <form class="bg-surface-container-low rounded-xl p-4 flex flex-wrap items-center gap-4" method="get">
            <input name="q" type="hidden" value="<?= admin_html((string)$queryFilters['q']) ?>"/>
            <input name="page" type="hidden" value="1"/>

            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-on-surface-variant">Status:</span>
                <select class="rounded-full border-outline-variant bg-white py-2 px-4 focus:ring-2 focus:ring-primary-container" name="status">
                    <option value="" <?= $queryFilters['status'] === '' ? 'selected' : '' ?>>All</option>
                    <?php foreach (shippingFilterStatusOptions() as $statusOption): ?>
                        <option value="<?= admin_html($statusOption) ?>" <?= $queryFilters['status'] === $statusOption ? 'selected' : '' ?>><?= admin_html(shippingStatusLabel($statusOption)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-on-surface-variant">Courier:</span>
                <select class="rounded-full border-outline-variant bg-white py-2 px-4 focus:ring-2 focus:ring-primary-container" name="courier">
                    <option value="" <?= $queryFilters['courier'] === '' ? 'selected' : '' ?>>Any</option>
                    <option value="__unassigned__" <?= $queryFilters['courier'] === '__unassigned__' ? 'selected' : '' ?>>Unassigned</option>
                    <?php foreach ($courierOptions as $courier): ?>
                        <option value="<?= admin_html($courier) ?>" <?= $queryFilters['courier'] === $courier ? 'selected' : '' ?>><?= admin_html($courier) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-on-surface-variant">Per page:</span>
                <select class="rounded-full border-outline-variant bg-white py-2 px-4 focus:ring-2 focus:ring-primary-container" name="per_page">
                    <?php foreach ([10, 20, 50, 100] as $sizeOption): ?>
                        <option value="<?= $sizeOption ?>" <?= (int)$queryFilters['per_page'] === $sizeOption ? 'selected' : '' ?>><?= $sizeOption ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button class="ml-auto px-5 py-2 bg-primary text-white rounded-full font-semibold hover:opacity-90 transition-opacity" type="submit">
                Apply
            </button>
            <a class="px-5 py-2 text-primary font-semibold rounded-full hover:bg-primary-container transition-colors" href="manageShipping.php<?= admin_html($clearQuery) ?>">
                Clear
            </a>
        </form>

        <section class="bg-white rounded-xl soft-shadow border border-outline-variant overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse min-w-[1200px]">
                    <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant text-sm uppercase tracking-wide">
                        <th class="px-4 py-3 text-left">Order</th>
                        <th class="px-4 py-3 text-left">Customer</th>
                        <th class="px-4 py-3 text-left">Delivery Address</th>
                        <th class="px-4 py-3 text-left">Courier</th>
                        <th class="px-4 py-3 text-left">Tracking</th>
                        <th class="px-4 py-3 text-left">Shipping Fee</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                    <?php if (count($shipments) === 0): ?>
                        <tr>
                            <td class="px-6 py-8 text-center text-on-surface-variant" colspan="8">
                                No shipping records found for current filters.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($shipments as $row): ?>
                        <?php $isDelivered = (string)$row['effective_shipping_status'] === 'delivered'; ?>
                        <tr class="shipping-row transition-colors">
                            <td class="px-4 py-4 align-top">
                                <div class="font-semibold text-primary">#<?= admin_html((string)$row['order_number']) ?></div>
                                <div class="text-xs text-on-surface-variant mt-1"><?= admin_html(formatShippingDate((string)$row['order_datetime'])) ?>, <?= admin_html(formatShippingTime((string)$row['order_datetime'])) ?></div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="font-semibold"><?= admin_html((string)$row['customer_name']) ?></div>
                                <div class="text-xs text-on-surface-variant mt-1"><?= admin_html((string)$row['customer_email']) ?></div>
                            </td>
                            <td class="px-4 py-4 align-top max-w-[320px]">
                                <div class="font-semibold text-sm"><?= admin_html((string)$row['delivery_contact']) ?></div>
                                <div class="text-sm text-on-surface-variant mt-1"><?= admin_html((string)$row['delivery_address']) ?></div>
                                <?php if ((string)$row['delivery_meta'] !== ''): ?>
                                    <div class="text-xs text-on-surface-variant mt-1"><?= admin_html((string)$row['delivery_meta']) ?></div>
                                <?php endif; ?>
                                <details class="mt-2">
                                    <summary class="cursor-pointer text-xs text-primary hover:underline">View delivery address</summary>
                                    <p class="text-xs text-on-surface-variant mt-2 whitespace-pre-line"><?= nl2br(admin_html((string)$row['delivery_full'])) ?></p>
                                </details>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <?php if ($shipmentsTableExists): ?>
                                    <form class="flex items-center gap-2" method="post">
                                        <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
                                        <input name="action" type="hidden" value="assign_courier"/>
                                        <input name="order_id" type="hidden" value="<?= (int)$row['order_id'] ?>"/>
                                        <input name="return_query" type="hidden" value="<?= admin_html($baseQuery) ?>"/>
                                        <input class="w-40 rounded-lg border-outline-variant text-sm" maxlength="120" name="courier_name" placeholder="Courier name" type="text" value="<?= admin_html((string)$row['courier_name']) ?>"/>
                                        <button class="px-3 py-2 rounded-lg bg-primary text-white text-xs font-semibold hover:opacity-90" type="submit">Assign</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-sm text-on-surface-variant">Unavailable</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <?php if ($shipmentsTableExists): ?>
                                    <form class="flex items-center gap-2" method="post">
                                        <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
                                        <input name="action" type="hidden" value="add_tracking_number"/>
                                        <input name="order_id" type="hidden" value="<?= (int)$row['order_id'] ?>"/>
                                        <input name="return_query" type="hidden" value="<?= admin_html($baseQuery) ?>"/>
                                        <input class="w-44 rounded-lg border-outline-variant text-sm" maxlength="120" name="tracking_number" placeholder="Tracking #" type="text" value="<?= admin_html((string)$row['tracking_number']) ?>"/>
                                        <button class="px-3 py-2 rounded-lg bg-slate-800 text-white text-xs font-semibold hover:bg-slate-700" type="submit">Save</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-sm text-on-surface-variant">Unavailable</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <?php if ($shipmentsTableExists): ?>
                                    <form class="flex items-center gap-2" method="post">
                                        <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
                                        <input name="action" type="hidden" value="set_shipping_fee"/>
                                        <input name="order_id" type="hidden" value="<?= (int)$row['order_id'] ?>"/>
                                        <input name="return_query" type="hidden" value="<?= admin_html($baseQuery) ?>"/>
                                        <input class="w-28 rounded-lg border-outline-variant text-sm" min="0" name="shipping_fee" step="0.01" type="number" value="<?= admin_html(number_format((float)$row['effective_shipping_fee'], 2, '.', '')) ?>"/>
                                        <button class="px-3 py-2 rounded-lg bg-tertiary-container text-on-tertiary-container text-xs font-semibold hover:opacity-90" type="submit">Set</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-sm text-on-surface-variant"><?= admin_html(admin_format_money((float)$row['effective_shipping_fee'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <span class="<?= shippingStatusBadgeClass((string)$row['effective_shipping_status']) ?>">
                                    <?= admin_html(shippingStatusLabel((string)$row['effective_shipping_status'])) ?>
                                </span>
                                <?php if ($shipmentsTableExists): ?>
                                    <form class="mt-2 flex items-center gap-2" method="post">
                                        <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
                                        <input name="action" type="hidden" value="update_delivery_status"/>
                                        <input name="order_id" type="hidden" value="<?= (int)$row['order_id'] ?>"/>
                                        <input name="return_query" type="hidden" value="<?= admin_html($baseQuery) ?>"/>
                                        <select class="w-36 rounded-lg border-outline-variant text-sm" name="delivery_status">
                                            <?php foreach (shippingEditableStatusOptions() as $statusOption): ?>
                                                <option value="<?= admin_html($statusOption) ?>" <?= (string)$row['effective_shipping_status'] === $statusOption ? 'selected' : '' ?>><?= admin_html(shippingStatusLabel($statusOption)) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="px-3 py-2 rounded-lg bg-secondary-container text-on-secondary-container text-xs font-semibold hover:opacity-90" type="submit">Update</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 align-top text-right">
                                <?php if ($shipmentsTableExists): ?>
                                    <form method="post">
                                        <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
                                        <input name="action" type="hidden" value="mark_delivered"/>
                                        <input name="order_id" type="hidden" value="<?= (int)$row['order_id'] ?>"/>
                                        <input name="return_query" type="hidden" value="<?= admin_html($baseQuery) ?>"/>
                                        <button class="px-4 py-2 rounded-lg text-xs font-semibold <?= $isDelivered ? 'bg-slate-200 text-slate-500 cursor-not-allowed' : 'bg-secondary text-white hover:opacity-90' ?>" <?= $isDelivered ? 'disabled' : '' ?> type="submit">
                                            Mark as delivered
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-sm text-on-surface-variant">Unavailable</span>
                                <?php endif; ?>
                                <a class="inline-flex mt-2 text-xs text-primary hover:underline" href="manageOrders.php?view_id=<?= (int)$row['order_id'] ?>">View order</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <p class="text-sm text-on-surface-variant">
                    Showing <?= number_format($startRow) ?> to <?= number_format($endRow) ?> of <?= number_format($totalRows) ?> orders
                </p>
                <div class="flex items-center gap-2">
                    <?php
                    $prevPage = max(1, $queryFilters['page'] - 1);
                    $nextPage = min($totalPages, $queryFilters['page'] + 1);
                    ?>
                    <a class="w-9 h-9 flex items-center justify-center rounded-full border border-outline-variant <?= $queryFilters['page'] <= 1 ? 'pointer-events-none opacity-40' : 'hover:bg-surface-container-high' ?>" href="manageShipping.php<?= admin_html(buildShippingFilterQuery(array_merge($queryFilters, ['page' => $prevPage]))) ?>">
                        <span class="material-symbols-outlined text-sm">chevron_left</span>
                    </a>
                    <?php
                    $pageStart = max(1, $queryFilters['page'] - 2);
                    $pageEnd = min($totalPages, $queryFilters['page'] + 2);
                    for ($page = $pageStart; $page <= $pageEnd; $page++):
                        $isActivePage = $page === (int)$queryFilters['page'];
                        ?>
                        <a class="w-9 h-9 flex items-center justify-center rounded-full text-sm font-semibold <?= $isActivePage ? 'bg-primary text-white' : 'border border-outline-variant hover:bg-surface-container-high' ?>" href="manageShipping.php<?= admin_html(buildShippingFilterQuery(array_merge($queryFilters, ['page' => $page]))) ?>">
                            <?= $page ?>
                        </a>
                    <?php endfor; ?>
                    <a class="w-9 h-9 flex items-center justify-center rounded-full border border-outline-variant <?= $queryFilters['page'] >= $totalPages ? 'pointer-events-none opacity-40' : 'hover:bg-surface-container-high' ?>" href="manageShipping.php<?= admin_html(buildShippingFilterQuery(array_merge($queryFilters, ['page' => $nextPage]))) ?>">
                        <span class="material-symbols-outlined text-sm">chevron_right</span>
                    </a>
                </div>
            </div>
        </section>
    </div>
</main>
</body>
</html>
<?php admin_page_cache_finish(); ?>

<?php

function shippingEditableStatusOptions(): array
{
    return ['preparing', 'shipped', 'in_transit', 'delivered', 'returned'];
}

function shippingFilterStatusOptions(): array
{
    return ['preparing', 'shipped', 'in_transit', 'delivered', 'returned', 'cancelled'];
}

function handleShippingPostActions(PDO $pdo, array $admin, array $editableStatusOptions): void
{
    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        return;
    }

    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    if (!admin_validate_csrf_token($csrfToken)) {
        setShippingFlash('error', 'Invalid form token. Please try again.');
        redirectManageShipping((string)($_POST['return_query'] ?? ''));
    }

    $action = trim((string)($_POST['action'] ?? ''));
    $returnQuery = (string)($_POST['return_query'] ?? '');

    try {
        if (!admin_table_exists($pdo, 'orders') || !admin_table_exists($pdo, 'shipments')) {
            throw new RuntimeException('Shipping backend tables are missing.');
        }

        switch ($action) {
            case 'assign_courier':
                $order = fetchOrderForShippingAction($pdo, max(0, (int)($_POST['order_id'] ?? 0)));
                if ($order === null) {
                    throw new RuntimeException('Order not found.');
                }

                $courierName = trim((string)($_POST['courier_name'] ?? ''));
                if ($courierName === '') {
                    throw new InvalidArgumentException('Courier name is required.');
                }

                $pdo->beginTransaction();
                ensureShipmentForOrder($pdo, $order);
                $statement = $pdo->prepare(
                    'UPDATE shipments SET courier_name = :courier_name, updated_at = NOW() WHERE order_id = :order_id'
                );
                $statement->execute([
                    ':courier_name' => $courierName,
                    ':order_id' => (int)$order['id'],
                ]);

                if ((string)$order['shipping_status'] === 'not_shipped') {
                    updateOrderShippingStatus($pdo, (int)$order['id'], 'preparing');
                }

                $pdo->commit();

                setShippingFlash('success', 'Courier assigned successfully.');
                admin_log_activity($pdo, (int)$admin['id'], 'shipping.assign_courier', "Order #{$order['order_number']} courier => {$courierName}");
                redirectManageShipping($returnQuery);
                break;

            case 'add_tracking_number':
                $order = fetchOrderForShippingAction($pdo, max(0, (int)($_POST['order_id'] ?? 0)));
                if ($order === null) {
                    throw new RuntimeException('Order not found.');
                }

                $trackingNumber = trim((string)($_POST['tracking_number'] ?? ''));
                if ($trackingNumber === '') {
                    throw new InvalidArgumentException('Tracking number is required.');
                }
                if (strlen($trackingNumber) > 120) {
                    throw new InvalidArgumentException('Tracking number is too long.');
                }

                $pdo->beginTransaction();
                $shipment = ensureShipmentForOrder($pdo, $order);

                $updateSql = 'UPDATE shipments SET tracking_number = :tracking_number, updated_at = NOW()';
                $params = [
                    ':tracking_number' => $trackingNumber,
                    ':order_id' => (int)$order['id'],
                ];

                $currentStatus = (string)($shipment['status'] ?? '');
                if (in_array($currentStatus, ['preparing', ''], true)) {
                    $updateSql .= ", status = 'shipped', shipped_at = COALESCE(shipped_at, NOW())";
                    updateOrderShippingStatus($pdo, (int)$order['id'], 'shipped');
                }

                $updateSql .= ' WHERE order_id = :order_id';
                $statement = $pdo->prepare($updateSql);
                $statement->execute($params);

                $pdo->commit();

                setShippingFlash('success', 'Tracking number saved.');
                admin_log_activity($pdo, (int)$admin['id'], 'shipping.tracking', "Order #{$order['order_number']} tracking => {$trackingNumber}");
                redirectManageShipping($returnQuery);
                break;

            case 'update_delivery_status':
                $order = fetchOrderForShippingAction($pdo, max(0, (int)($_POST['order_id'] ?? 0)));
                if ($order === null) {
                    throw new RuntimeException('Order not found.');
                }

                $deliveryStatus = trim((string)($_POST['delivery_status'] ?? ''));
                if (!in_array($deliveryStatus, $editableStatusOptions, true)) {
                    throw new InvalidArgumentException('Invalid delivery status.');
                }

                $pdo->beginTransaction();
                ensureShipmentForOrder($pdo, $order);

                $sql = 'UPDATE shipments SET status = :status, updated_at = NOW()';
                if (in_array($deliveryStatus, ['shipped', 'in_transit', 'delivered'], true)) {
                    $sql .= ', shipped_at = COALESCE(shipped_at, NOW())';
                }
                if ($deliveryStatus === 'delivered') {
                    $sql .= ', delivered_at = NOW()';
                }
                if ($deliveryStatus !== 'delivered') {
                    $sql .= ', delivered_at = NULL';
                }
                $sql .= ' WHERE order_id = :order_id';

                $statement = $pdo->prepare($sql);
                $statement->execute([
                    ':status' => $deliveryStatus,
                    ':order_id' => (int)$order['id'],
                ]);

                updateOrderShippingStatus($pdo, (int)$order['id'], normalizeShipmentStatusForOrder($deliveryStatus));
                $pdo->commit();

                setShippingFlash('success', 'Delivery status updated.');
                admin_log_activity($pdo, (int)$admin['id'], 'shipping.status', "Order #{$order['order_number']} status => {$deliveryStatus}");
                redirectManageShipping($returnQuery);
                break;

            case 'set_shipping_fee':
                $order = fetchOrderForShippingAction($pdo, max(0, (int)($_POST['order_id'] ?? 0)));
                if ($order === null) {
                    throw new RuntimeException('Order not found.');
                }

                $shippingFee = parseShippingFee((string)($_POST['shipping_fee'] ?? ''));

                $pdo->beginTransaction();
                ensureShipmentForOrder($pdo, $order);

                $statement = $pdo->prepare(
                    'UPDATE shipments SET shipping_fee = :shipping_fee, updated_at = NOW() WHERE order_id = :order_id'
                );
                $statement->execute([
                    ':shipping_fee' => $shippingFee,
                    ':order_id' => (int)$order['id'],
                ]);

                $orderStatement = $pdo->prepare(
                    'UPDATE orders
                     SET shipping_fee = :shipping_fee,
                         total_amount = ROUND((subtotal - discount_amount + tax_amount + :shipping_fee), 2),
                         updated_at = NOW()
                     WHERE id = :order_id'
                );
                $orderStatement->execute([
                    ':shipping_fee' => $shippingFee,
                    ':order_id' => (int)$order['id'],
                ]);

                $pdo->commit();

                setShippingFlash('success', 'Shipping fee updated.');
                admin_log_activity($pdo, (int)$admin['id'], 'shipping.fee', "Order #{$order['order_number']} fee => {$shippingFee}");
                redirectManageShipping($returnQuery);
                break;

            case 'mark_delivered':
                $order = fetchOrderForShippingAction($pdo, max(0, (int)($_POST['order_id'] ?? 0)));
                if ($order === null) {
                    throw new RuntimeException('Order not found.');
                }

                $pdo->beginTransaction();
                ensureShipmentForOrder($pdo, $order);
                $statement = $pdo->prepare(
                    "UPDATE shipments
                     SET status = 'delivered',
                         shipped_at = COALESCE(shipped_at, NOW()),
                         delivered_at = NOW(),
                         updated_at = NOW()
                     WHERE order_id = :order_id"
                );
                $statement->execute([':order_id' => (int)$order['id']]);

                updateOrderShippingStatus($pdo, (int)$order['id'], 'delivered');
                $pdo->commit();

                setShippingFlash('success', 'Order marked as delivered.');
                admin_log_activity($pdo, (int)$admin['id'], 'shipping.delivered', "Order #{$order['order_number']} marked delivered");
                redirectManageShipping($returnQuery);
                break;

            default:
                throw new InvalidArgumentException('Unknown shipping action.');
        }
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        setShippingFlash('error', $exception->getMessage());
        redirectManageShipping($returnQuery);
    }
}

function fetchShippingStats(PDO $pdo): array
{
    $cacheKey = admin_cache_key('shipping_stats', ['v' => 1]);
    $cached = null;
    if (admin_cache_fetch($cacheKey, $cached) && is_array($cached)) {
        return array_merge(defaultShippingStats(), $cached);
    }

    $stats = defaultShippingStats();

    $statusExpression = "COALESCE(s.status, CASE WHEN o.shipping_status = 'not_shipped' THEN 'preparing' ELSE o.shipping_status END)";

    $sql = "
        SELECT
            COUNT(*) AS total_orders,
            SUM(CASE WHEN {$statusExpression} = 'delivered' THEN 1 ELSE 0 END) AS delivered_count,
            SUM(CASE WHEN {$statusExpression} IN ('shipped', 'in_transit') THEN 1 ELSE 0 END) AS moving_count,
            SUM(CASE WHEN {$statusExpression} = 'preparing' THEN 1 ELSE 0 END) AS preparing_count,
            SUM(
                CASE
                    WHEN s.order_id IS NULL
                         OR s.courier_name IS NULL
                         OR TRIM(s.courier_name) = ''
                         OR s.tracking_number IS NULL
                         OR TRIM(s.tracking_number) = ''
                    THEN 1
                    ELSE 0
                END
            ) AS pending_assignment,
            COALESCE(SUM(COALESCE(s.shipping_fee, o.shipping_fee)), 0) AS shipping_revenue
        FROM orders o
        LEFT JOIN shipments s ON s.order_id = o.id
    ";

    $row = $pdo->query($sql)->fetch();
    if (!is_array($row)) {
        return $stats;
    }

    $stats['total_orders'] = (int)($row['total_orders'] ?? 0);
    $stats['delivered_count'] = (int)($row['delivered_count'] ?? 0);
    $stats['moving_count'] = (int)($row['moving_count'] ?? 0);
    $stats['preparing_count'] = (int)($row['preparing_count'] ?? 0);
    $stats['pending_assignment'] = (int)($row['pending_assignment'] ?? 0);
    $stats['shipping_revenue'] = (float)($row['shipping_revenue'] ?? 0.0);

    admin_cache_store($cacheKey, $stats, ADMIN_PAGE_CACHE_TTL_SECONDS);
    return $stats;
}

function fetchShippingList(
    PDO $pdo,
    array $filters,
    bool $shipmentsTableExists,
    bool $usersTableExists,
    bool $addressesTableExists
): array {
    $cacheKey = admin_cache_key('shipping_list', [
        'filters' => $filters,
        'shipments_table_exists' => $shipmentsTableExists,
        'users_table_exists' => $usersTableExists,
        'addresses_table_exists' => $addressesTableExists,
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

    $where = ['1=1'];
    $params = [];

    if ($filters['q'] !== '') {
        $searchParts = ['o.order_number LIKE :q'];
        if ($usersTableExists) {
            $searchParts[] = 'u.name LIKE :q';
            $searchParts[] = 'u.email LIKE :q';
        }
        if ($shipmentsTableExists) {
            $searchParts[] = 's.courier_name LIKE :q';
            $searchParts[] = 's.tracking_number LIKE :q';
        }
        $where[] = '(' . implode(' OR ', $searchParts) . ')';
        $params[':q'] = '%' . $filters['q'] . '%';
    }

    $effectiveStatusExpression = $shipmentsTableExists
        ? "COALESCE(s.status, CASE WHEN o.shipping_status = 'not_shipped' THEN 'preparing' ELSE o.shipping_status END)"
        : "CASE WHEN o.shipping_status = 'not_shipped' THEN 'preparing' ELSE o.shipping_status END";

    if ($filters['status'] !== '') {
        $where[] = $effectiveStatusExpression . ' = :status';
        $params[':status'] = $filters['status'];
    }

    if ($filters['courier'] !== '') {
        if ($filters['courier'] === '__unassigned__') {
            if ($shipmentsTableExists) {
                $where[] = '(s.courier_name IS NULL OR TRIM(s.courier_name) = \'\')';
            }
        } else {
            if ($shipmentsTableExists) {
                $where[] = 's.courier_name = :courier';
                $params[':courier'] = $filters['courier'];
            } else {
                $where[] = '1 = 0';
            }
        }
    }

    $joins = [];
    if ($usersTableExists) {
        $joins[] = 'LEFT JOIN users u ON u.id = o.user_id';
    }
    if ($shipmentsTableExists) {
        $joins[] = 'LEFT JOIN shipments s ON s.order_id = o.id';
    }
    $joinSql = implode("\n", $joins);
    $whereSql = implode(' AND ', $where);

    $countSql = "
        SELECT COUNT(*)
        FROM orders o
        {$joinSql}
        WHERE {$whereSql}
    ";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $offset = max(0, ((int)$filters['page'] - 1) * (int)$filters['per_page']);

    $customerNameExpression = $usersTableExists
        ? "COALESCE(NULLIF(u.name, ''), NULLIF(u.email, ''), 'Guest')"
        : "'Guest'";
    $customerEmailExpression = $usersTableExists
        ? "COALESCE(NULLIF(u.email, ''), 'guest@example.com')"
        : "'guest@example.com'";

    $courierExpression = $shipmentsTableExists ? 's.courier_name' : "''";
    $trackingExpression = $shipmentsTableExists ? 's.tracking_number' : "''";
    $addressExpression = $shipmentsTableExists ? 's.shipping_address' : "NULL";
    $feeExpression = $shipmentsTableExists ? 'COALESCE(s.shipping_fee, o.shipping_fee)' : 'o.shipping_fee';

    $sql = "
        SELECT
            o.id AS order_id,
            o.order_number,
            o.user_id,
            o.shipping_status AS order_shipping_status,
            o.shipping_fee AS order_shipping_fee,
            COALESCE(o.placed_at, o.created_at) AS order_datetime,
            {$customerNameExpression} AS customer_name,
            {$customerEmailExpression} AS customer_email,
            {$courierExpression} AS courier_name,
            {$trackingExpression} AS tracking_number,
            {$addressExpression} AS shipping_address,
            {$feeExpression} AS effective_shipping_fee,
            {$effectiveStatusExpression} AS effective_shipping_status
        FROM orders o
        {$joinSql}
        WHERE {$whereSql}
        ORDER BY COALESCE(o.placed_at, o.created_at) DESC, o.id DESC
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
    $resultRows = is_array($rows) ? $rows : [];
    $resultRows = hydrateShippingAddressRows($pdo, $resultRows, $addressesTableExists);

    $result = ['rows' => $resultRows, 'total' => $total];
    admin_cache_store($cacheKey, $result, 15);
    return $result;
}

function hydrateShippingAddressRows(PDO $pdo, array $rows, bool $addressesTableExists): array
{
    if (count($rows) === 0) {
        return [];
    }

    $needsFallbackUserIds = [];
    foreach ($rows as $row) {
        $payload = decodeShippingAddressPayload((string)($row['shipping_address'] ?? ''));
        if (count($payload) > 0) {
            continue;
        }

        $userId = (int)($row['user_id'] ?? 0);
        if ($userId > 0) {
            $needsFallbackUserIds[] = $userId;
        }
    }

    $fallbackAddressMap = [];
    if ($addressesTableExists && count($needsFallbackUserIds) > 0) {
        $fallbackAddressMap = fetchDefaultAddressMap($pdo, array_values(array_unique($needsFallbackUserIds)));
    }

    foreach ($rows as &$row) {
        $payload = decodeShippingAddressPayload((string)($row['shipping_address'] ?? ''));
        $source = 'Shipment';
        $userId = (int)($row['user_id'] ?? 0);

        if (count($payload) === 0 && $userId > 0 && isset($fallbackAddressMap[$userId])) {
            $payload = $fallbackAddressMap[$userId];
            $source = 'Saved Address';
        }

        if (count($payload) === 0) {
            $source = 'No Address';
        }

        $contact = buildAddressContact($payload, (string)($row['customer_name'] ?? 'Guest'));
        $addressLine = buildAddressLine($payload);
        $addressFull = buildAddressFull($payload, $contact);

        $row['delivery_contact'] = $contact;
        $row['delivery_address'] = $addressLine === '' ? 'No delivery address saved.' : $addressLine;
        $row['delivery_meta'] = $source;
        $row['delivery_full'] = $addressFull === '' ? $row['delivery_address'] : $addressFull;
    }
    unset($row);

    return $rows;
}

function fetchDefaultAddressMap(PDO $pdo, array $userIds): array
{
    if (count($userIds) === 0) {
        return [];
    }

    sort($userIds);
    $cacheKey = admin_cache_key('shipping_default_address_map', [
        'user_ids' => $userIds,
        'v' => 1,
    ]);
    $cached = null;
    if (admin_cache_fetch($cacheKey, $cached) && is_array($cached)) {
        return $cached;
    }

    $placeholders = implode(', ', array_fill(0, count($userIds), '?'));
    $sql = "
        SELECT
            user_id,
            full_name,
            phone,
            address_line1,
            address_line2,
            city,
            township,
            state,
            country,
            postal_code,
            is_default
        FROM addresses
        WHERE user_id IN ({$placeholders})
        ORDER BY user_id ASC, is_default DESC, id DESC
    ";

    $statement = $pdo->prepare($sql);
    foreach ($userIds as $index => $userId) {
        $statement->bindValue($index + 1, (int)$userId, PDO::PARAM_INT);
    }
    $statement->execute();
    $rows = $statement->fetchAll();

    $map = [];
    if (!is_array($rows)) {
        return $map;
    }

    foreach ($rows as $row) {
        $userId = (int)($row['user_id'] ?? 0);
        if ($userId <= 0 || isset($map[$userId])) {
            continue;
        }

        $map[$userId] = [
            'full_name' => (string)($row['full_name'] ?? ''),
            'phone' => (string)($row['phone'] ?? ''),
            'address_line1' => (string)($row['address_line1'] ?? ''),
            'address_line2' => (string)($row['address_line2'] ?? ''),
            'city' => (string)($row['city'] ?? ''),
            'township' => (string)($row['township'] ?? ''),
            'state' => (string)($row['state'] ?? ''),
            'country' => (string)($row['country'] ?? ''),
            'postal_code' => (string)($row['postal_code'] ?? ''),
        ];
    }

    admin_cache_store($cacheKey, $map, ADMIN_PAGE_CACHE_TTL_SECONDS);
    return $map;
}

function fetchOrderForShippingAction(PDO $pdo, int $orderId): ?array
{
    if ($orderId <= 0) {
        return null;
    }

    $statement = $pdo->prepare(
        'SELECT id, order_number, user_id, shipping_status, shipping_fee FROM orders WHERE id = :id LIMIT 1'
    );
    $statement->execute([':id' => $orderId]);
    $row = $statement->fetch();

    return is_array($row) ? $row : null;
}

function ensureShipmentForOrder(PDO $pdo, array $order): array
{
    $orderId = (int)($order['id'] ?? 0);
    if ($orderId <= 0) {
        throw new InvalidArgumentException('Invalid order ID.');
    }

    $existing = fetchShipmentByOrderId($pdo, $orderId);
    if ($existing !== null) {
        return $existing;
    }

    $addressPayload = [];
    $userId = (int)($order['user_id'] ?? 0);
    if ($userId > 0 && admin_table_exists($pdo, 'addresses')) {
        $addressPayload = fetchAddressPayloadForUser($pdo, $userId);
    }

    $status = normalizeOrderStatusForShipment((string)($order['shipping_status'] ?? ''));
    $shippingFee = round((float)($order['shipping_fee'] ?? 0.0), 2);
    $shippedAt = in_array($status, ['shipped', 'in_transit', 'delivered'], true) ? gmdate('Y-m-d H:i:s') : null;
    $deliveredAt = $status === 'delivered' ? gmdate('Y-m-d H:i:s') : null;

    $statement = $pdo->prepare(
        'INSERT INTO shipments (
            order_id, courier_name, tracking_number, shipping_address, shipping_fee, status, shipped_at, delivered_at, created_at, updated_at
        ) VALUES (
            :order_id, NULL, NULL, :shipping_address, :shipping_fee, :status, :shipped_at, :delivered_at, NOW(), NOW()
        )'
    );
    $statement->execute([
        ':order_id' => $orderId,
        ':shipping_address' => encodeShippingAddressPayload($addressPayload),
        ':shipping_fee' => $shippingFee,
        ':status' => $status,
        ':shipped_at' => $shippedAt,
        ':delivered_at' => $deliveredAt,
    ]);

    $shipment = fetchShipmentByOrderId($pdo, $orderId);
    if ($shipment === null) {
        throw new RuntimeException('Failed to create shipment row.');
    }

    return $shipment;
}

function fetchShipmentByOrderId(PDO $pdo, int $orderId): ?array
{
    $statement = $pdo->prepare('SELECT * FROM shipments WHERE order_id = :order_id LIMIT 1');
    $statement->execute([':order_id' => $orderId]);
    $row = $statement->fetch();

    return is_array($row) ? $row : null;
}

function fetchAddressPayloadForUser(PDO $pdo, int $userId): array
{
    if ($userId <= 0) {
        return [];
    }

    $statement = $pdo->prepare(
        'SELECT
            full_name,
            phone,
            address_line1,
            address_line2,
            city,
            township,
            state,
            country,
            postal_code
        FROM addresses
        WHERE user_id = :user_id
        ORDER BY is_default DESC, id DESC
        LIMIT 1'
    );
    $statement->execute([':user_id' => $userId]);
    $row = $statement->fetch();
    if (!is_array($row)) {
        return [];
    }

    return [
        'full_name' => (string)($row['full_name'] ?? ''),
        'phone' => (string)($row['phone'] ?? ''),
        'address_line1' => (string)($row['address_line1'] ?? ''),
        'address_line2' => (string)($row['address_line2'] ?? ''),
        'city' => (string)($row['city'] ?? ''),
        'township' => (string)($row['township'] ?? ''),
        'state' => (string)($row['state'] ?? ''),
        'country' => (string)($row['country'] ?? ''),
        'postal_code' => (string)($row['postal_code'] ?? ''),
    ];
}

function parseShippingFee(string $value): float
{
    $normalized = trim(str_replace([',', '$', ' '], '', $value));
    if ($normalized === '' || !is_numeric($normalized)) {
        throw new InvalidArgumentException('Shipping fee must be a valid number.');
    }

    $amount = round((float)$normalized, 2);
    if ($amount < 0) {
        throw new InvalidArgumentException('Shipping fee cannot be negative.');
    }
    if ($amount > 9999999.99) {
        throw new InvalidArgumentException('Shipping fee is too large.');
    }

    return $amount;
}

function updateOrderShippingStatus(PDO $pdo, int $orderId, string $shippingStatus): void
{
    $allowed = ['not_shipped', 'preparing', 'shipped', 'in_transit', 'delivered', 'returned'];
    if (!in_array($shippingStatus, $allowed, true)) {
        throw new InvalidArgumentException('Unsupported order shipping status.');
    }

    $statement = $pdo->prepare(
        'UPDATE orders SET shipping_status = :shipping_status, updated_at = NOW() WHERE id = :id'
    );
    $statement->execute([
        ':shipping_status' => $shippingStatus,
        ':id' => $orderId,
    ]);
}

function normalizeShipmentStatusForOrder(string $shipmentStatus): string
{
    return match ($shipmentStatus) {
        'preparing' => 'preparing',
        'shipped' => 'shipped',
        'in_transit' => 'in_transit',
        'delivered' => 'delivered',
        'returned', 'cancelled' => 'returned',
        default => 'not_shipped',
    };
}

function normalizeOrderStatusForShipment(string $orderShippingStatus): string
{
    return match ($orderShippingStatus) {
        'preparing', 'shipped', 'in_transit', 'delivered', 'returned' => $orderShippingStatus,
        default => 'preparing',
    };
}

function fetchCourierOptions(PDO $pdo): array
{
    $cacheKey = admin_cache_key('shipping_courier_options', ['v' => 1]);
    $cached = null;
    if (admin_cache_fetch($cacheKey, $cached) && is_array($cached)) {
        return $cached;
    }

    $rows = $pdo->query(
        "SELECT DISTINCT TRIM(courier_name) AS courier_name
         FROM shipments
         WHERE courier_name IS NOT NULL
           AND TRIM(courier_name) <> ''
         ORDER BY courier_name ASC"
    )->fetchAll();

    if (!is_array($rows)) {
        return [];
    }

    $options = [];
    foreach ($rows as $row) {
        $courier = trim((string)($row['courier_name'] ?? ''));
        if ($courier !== '') {
            $options[] = $courier;
        }
    }

    admin_cache_store($cacheKey, $options, 60);
    return $options;
}

function decodeShippingAddressPayload(string $payload): array
{
    $payload = trim($payload);
    if ($payload === '' || $payload === 'null') {
        return [];
    }

    $decoded = json_decode($payload, true);
    if (!is_array($decoded)) {
        return [];
    }

    return $decoded;
}

function encodeShippingAddressPayload(array $payload): string
{
    if (count($payload) === 0) {
        return '{}';
    }

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return is_string($json) ? $json : '{}';
}

function addressValue(array $payload, array $keys): string
{
    foreach ($keys as $key) {
        if (!array_key_exists($key, $payload)) {
            continue;
        }
        $value = trim((string)$payload[$key]);
        if ($value !== '') {
            return $value;
        }
    }
    return '';
}

function buildAddressContact(array $payload, string $fallbackName): string
{
    $name = addressValue($payload, ['full_name', 'name', 'recipient_name']);
    if ($name === '') {
        $name = trim($fallbackName) !== '' ? trim($fallbackName) : 'Unknown Recipient';
    }

    $phone = addressValue($payload, ['phone', 'phone_number', 'mobile']);
    if ($phone === '') {
        return $name;
    }

    return $name . ' (' . $phone . ')';
}

function buildAddressLine(array $payload): string
{
    $line1 = addressValue($payload, ['address_line1', 'line1', 'address1']);
    $line2 = addressValue($payload, ['address_line2', 'line2', 'address2']);
    $city = addressValue($payload, ['city']);
    $township = addressValue($payload, ['township']);
    $state = addressValue($payload, ['state', 'region']);
    $country = addressValue($payload, ['country']);
    $postalCode = addressValue($payload, ['postal_code', 'zip', 'zip_code']);

    $streetParts = array_values(array_filter([$line1, $line2], static fn (string $value): bool => $value !== ''));
    $regionParts = array_values(array_filter([$township, $city, $state, $postalCode], static fn (string $value): bool => $value !== ''));

    $parts = [];
    if (count($streetParts) > 0) {
        $parts[] = implode(', ', $streetParts);
    }
    if (count($regionParts) > 0) {
        $parts[] = implode(', ', $regionParts);
    }
    if ($country !== '') {
        $parts[] = $country;
    }

    return implode(' | ', $parts);
}

function buildAddressFull(array $payload, string $contact): string
{
    $line1 = addressValue($payload, ['address_line1', 'line1', 'address1']);
    $line2 = addressValue($payload, ['address_line2', 'line2', 'address2']);
    $city = addressValue($payload, ['city']);
    $township = addressValue($payload, ['township']);
    $state = addressValue($payload, ['state', 'region']);
    $country = addressValue($payload, ['country']);
    $postalCode = addressValue($payload, ['postal_code', 'zip', 'zip_code']);

    $cityLineParts = array_values(array_filter([$township, $city, $state, $postalCode], static fn (string $value): bool => $value !== ''));
    $lines = [];

    if (trim($contact) !== '') {
        $lines[] = $contact;
    }
    if ($line1 !== '') {
        $lines[] = $line1;
    }
    if ($line2 !== '') {
        $lines[] = $line2;
    }
    if (count($cityLineParts) > 0) {
        $lines[] = implode(', ', $cityLineParts);
    }
    if ($country !== '') {
        $lines[] = $country;
    }

    return implode("\n", $lines);
}

function shippingStatusLabel(string $status): string
{
    return ucwords(str_replace('_', ' ', trim($status)));
}

function shippingStatusBadgeClass(string $status): string
{
    return match ($status) {
        'delivered' => 'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-secondary-container text-on-secondary-container',
        'in_transit', 'shipped' => 'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-primary-container text-on-primary-container',
        'preparing' => 'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-tertiary-container text-on-tertiary-container',
        'returned', 'cancelled' => 'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-error-container text-on-error-container',
        default => 'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-surface-container-high text-on-surface-variant',
    };
}

function formatShippingDate(string $datetime): string
{
    if ($datetime === '') {
        return 'N/A';
    }

    try {
        $date = new DateTimeImmutable($datetime);
        return $date->format('M d, Y');
    } catch (Throwable) {
        return $datetime;
    }
}

function formatShippingTime(string $datetime): string
{
    if ($datetime === '') {
        return '--';
    }

    try {
        $date = new DateTimeImmutable($datetime);
        return $date->format('h:i A');
    } catch (Throwable) {
        return '--';
    }
}

function defaultShippingStats(): array
{
    return [
        'total_orders' => 0,
        'delivered_count' => 0,
        'moving_count' => 0,
        'preparing_count' => 0,
        'pending_assignment' => 0,
        'shipping_revenue' => 0.0,
    ];
}

function setShippingFlash(string $type, string $message): void
{
    $_SESSION['admin_shipping_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function pullShippingFlash(): ?array
{
    if (!isset($_SESSION['admin_shipping_flash']) || !is_array($_SESSION['admin_shipping_flash'])) {
        return null;
    }

    $flash = $_SESSION['admin_shipping_flash'];
    unset($_SESSION['admin_shipping_flash']);

    return $flash;
}

function redirectManageShipping(string $returnQuery): never
{
    $safeQuery = sanitizeShippingReturnQuery($returnQuery);
    header('Location: manageShipping.php' . $safeQuery);
    exit;
}

function sanitizeShippingReturnQuery(string $query): string
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

function buildShippingFilterQuery(array $filters): string
{
    $query = http_build_query([
        'q' => (string)($filters['q'] ?? ''),
        'status' => (string)($filters['status'] ?? ''),
        'courier' => (string)($filters['courier'] ?? ''),
        'per_page' => (int)($filters['per_page'] ?? 10),
        'page' => (int)($filters['page'] ?? 1),
    ]);

    return $query === '' ? '' : '?' . $query;
}



