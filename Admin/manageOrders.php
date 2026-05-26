<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_layout.php';

admin_start_session();
$admin = admin_require_auth('adminLogin.php');
admin_page_cache_start($admin, 'orders', ADMIN_PAGE_CACHE_TTL_SECONDS);

$pdo = admin_db();
admin_ensure_tables($pdo);

$csrfToken = admin_bootstrap_csrf_token();

$orderStatusOptions = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
$paymentStatusOptions = ['unpaid', 'payment_pending_review', 'paid', 'failed', 'rejected', 'refunded', 'partial_refund'];
$shippingStatusOptions = ['not_shipped', 'preparing', 'shipped', 'in_transit', 'delivered', 'returned'];

$queryFilters = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
    'payment_status' => trim((string)($_GET['payment_status'] ?? '')),
    'page' => max(1, (int)($_GET['page'] ?? 1)),
    'per_page' => max(5, min(100, (int)($_GET['per_page'] ?? 10))),
];

$viewOrderId = max(0, (int)($_GET['view_id'] ?? 0));
$baseQuery = buildOrderFilterQuery($queryFilters);

handleOrderPostActions($pdo, $admin, $orderStatusOptions, $paymentStatusOptions, $shippingStatusOptions);

$flash = pullOrderFlash();
$ordersTableExists = admin_table_exists($pdo, 'orders');
$stats = $ordersTableExists ? fetchOrderStats($pdo) : defaultOrderStats();

$listData = $ordersTableExists ? fetchOrderList($pdo, $queryFilters) : ['rows' => [], 'total' => 0];
$orders = $listData['rows'];
$totalRows = (int)$listData['total'];
$totalPages = max(1, (int)ceil($totalRows / $queryFilters['per_page']));
$currentPage = min($queryFilters['page'], $totalPages);

if ($currentPage !== $queryFilters['page'] && $ordersTableExists) {
    $queryFilters['page'] = $currentPage;
    $baseQuery = buildOrderFilterQuery($queryFilters);
    $listData = fetchOrderList($pdo, $queryFilters);
    $orders = $listData['rows'];
}

$viewOrder = null;
$viewOrderItems = [];
if ($ordersTableExists && $viewOrderId > 0) {
    $viewOrder = fetchOrderById($pdo, $viewOrderId);
    if ($viewOrder !== null) {
        $viewOrderItems = fetchOrderItemsByOrderId($pdo, $viewOrderId);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Orders Management | LuvShop Admin</title>
    <?php admin_render_critical_css(); ?>
    <?php $adminCssHref = admin_css_href(); ?>
<?php if ($adminCssHref !== null): ?>
    <link href="<?= admin_html($adminCssHref) ?>" rel="stylesheet"/>
<?php endif; ?>
    <link href="<?= admin_html(admin_material_symbols_href()) ?>" rel="stylesheet"/>
<style>
        body {
            background-color: #fbf9f8;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1b1c1c;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .order-row:hover {
            background-color: rgba(255, 209, 220, 0.1);
        }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #fbf9f8; }
        ::-webkit-scrollbar-thumb { background: #d3c3c5; border-radius: 10px; }
    </style>
</head>
<body class="bg-surface text-on-surface">
<?php
admin_render_sidebar($admin, 'orders');
?>

<main class="ml-64 min-h-screen bg-surface" id="app-main">
    <?php
    admin_render_header($admin, [
        'search_action' => 'manageOrders.php',
        'search_method' => 'get',
        'search_name' => 'q',
        'search_value' => trim((string)$queryFilters['q']),
        'search_placeholder' => 'Search by order number',
        'search_hidden' => [
            'status' => (string)$queryFilters['status'],
            'payment_status' => (string)$queryFilters['payment_status'],
            'per_page' => (string)$queryFilters['per_page'],
            'page' => '1',
        ],
    ]);
    ?>
    <div class="px-lg py-xl max-w-[1280px] mx-auto">
        <?php if (!$ordersTableExists): ?>
            <div class="mb-lg rounded-lg bg-error-container border border-red-200 text-on-error-container px-lg py-md">
                Orders table was not found. Please create `orders` and related tables first.
            </div>
        <?php endif; ?>

        <?php if ($flash !== null): ?>
            <div class="mb-lg rounded-lg px-lg py-md border <?= $flash['type'] === 'error' ? 'bg-error-container border-red-200 text-on-error-container' : 'bg-secondary-container border-green-200 text-on-secondary-container' ?>">
                <?= admin_html($flash['message']) ?>
            </div>
        <?php endif; ?>

        <?php if ($viewOrder !== null): ?>
            <section class="mb-lg bg-white rounded-xl shadow-sm overflow-hidden border border-outline-variant">
                <div class="px-lg py-md bg-surface-container-low border-b border-outline-variant flex items-center justify-between">
                    <h3 class="font-headline-md text-primary">Order Detail: #<?= admin_html((string)$viewOrder['order_number']) ?></h3>
                    <a class="text-on-surface-variant hover:text-primary font-label-md" href="manageOrders.php<?= admin_html($baseQuery) ?>">Close</a>
                </div>
                <div class="p-lg grid grid-cols-1 lg:grid-cols-3 gap-lg">
                    <div class="space-y-sm">
                        <p class="font-label-sm text-on-surface-variant">Customer</p>
                        <p class="font-label-md"><?= admin_html((string)$viewOrder['customer_name']) ?></p>
                        <p class="text-sm text-on-surface-variant"><?= admin_html((string)$viewOrder['customer_email']) ?></p>
                    </div>
                    <div class="space-y-sm">
                        <p class="font-label-sm text-on-surface-variant">Date</p>
                        <p class="font-label-md"><?= admin_html(formatOrderDate((string)$viewOrder['order_datetime'])) ?></p>
                        <p class="text-sm text-on-surface-variant"><?= admin_html(formatOrderTime((string)$viewOrder['order_datetime'])) ?></p>
                    </div>
                    <div class="space-y-sm">
                        <p class="font-label-sm text-on-surface-variant">Total</p>
                        <p class="font-label-md"><?= admin_html(admin_format_money((float)$viewOrder['total_amount'])) ?></p>
                    </div>
                </div>
                <div class="px-lg pb-lg grid grid-cols-1 lg:grid-cols-3 gap-md">
                    <form class="bg-surface-container-low rounded-lg p-md flex items-center gap-sm" method="post">
                        <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
                        <input name="action" type="hidden" value="update_order_status"/>
                        <input name="order_id" type="hidden" value="<?= (int)$viewOrder['id'] ?>"/>
                        <input name="return_query" type="hidden" value="<?= admin_html($baseQuery . '&view_id=' . (int)$viewOrder['id']) ?>"/>
                        <label class="font-label-sm text-on-surface-variant">Order</label>
                        <select class="flex-1 rounded-full border-outline-variant bg-white" name="order_status">
                            <?php foreach ($orderStatusOptions as $status): ?>
                                <option value="<?= admin_html($status) ?>" <?= $status === (string)$viewOrder['order_status'] ? 'selected' : '' ?>><?= admin_html(ucfirst(str_replace('_', ' ', $status))) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="px-md py-xs rounded-full bg-primary text-on-primary font-label-sm" type="submit">Save</button>
                    </form>
                    <form class="bg-surface-container-low rounded-lg p-md flex items-center gap-sm" method="post">
                        <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
                        <input name="action" type="hidden" value="update_shipping_status"/>
                        <input name="order_id" type="hidden" value="<?= (int)$viewOrder['id'] ?>"/>
                        <input name="return_query" type="hidden" value="<?= admin_html($baseQuery . '&view_id=' . (int)$viewOrder['id']) ?>"/>
                        <label class="font-label-sm text-on-surface-variant">Shipping</label>
                        <select class="flex-1 rounded-full border-outline-variant bg-white" name="shipping_status">
                            <?php foreach ($shippingStatusOptions as $status): ?>
                                <option value="<?= admin_html($status) ?>" <?= $status === (string)$viewOrder['shipping_status'] ? 'selected' : '' ?>><?= admin_html(ucfirst(str_replace('_', ' ', $status))) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="px-md py-xs rounded-full bg-primary text-on-primary font-label-sm" type="submit">Save</button>
                    </form>
                    <form class="bg-surface-container-low rounded-lg p-md flex items-center gap-sm" method="post">
                        <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
                        <input name="action" type="hidden" value="update_payment_status"/>
                        <input name="order_id" type="hidden" value="<?= (int)$viewOrder['id'] ?>"/>
                        <input name="return_query" type="hidden" value="<?= admin_html($baseQuery . '&view_id=' . (int)$viewOrder['id']) ?>"/>
                        <label class="font-label-sm text-on-surface-variant">Payment</label>
                        <select class="flex-1 rounded-full border-outline-variant bg-white" name="payment_status">
                            <?php foreach ($paymentStatusOptions as $status): ?>
                                <option value="<?= admin_html($status) ?>" <?= $status === (string)$viewOrder['payment_status'] ? 'selected' : '' ?>><?= admin_html(ucfirst(str_replace('_', ' ', $status))) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="px-md py-xs rounded-full bg-primary text-on-primary font-label-sm" type="submit">Save</button>
                    </form>
                </div>
                <div class="px-lg pb-lg">
                    <h4 class="font-label-md text-on-surface mb-sm">Items</h4>
                    <div class="overflow-x-auto border border-outline-variant rounded-lg">
                        <table class="w-full">
                            <thead class="bg-surface-container-low text-on-surface-variant">
                            <tr>
                                <th class="text-left px-md py-sm font-label-sm">Item</th>
                                <th class="text-left px-md py-sm font-label-sm">SKU</th>
                                <th class="text-right px-md py-sm font-label-sm">Qty</th>
                                <th class="text-right px-md py-sm font-label-sm">Unit</th>
                                <th class="text-right px-md py-sm font-label-sm">Total</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant">
                            <?php if (count($viewOrderItems) === 0): ?>
                                <tr><td class="px-md py-md text-on-surface-variant" colspan="5">No order items found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($viewOrderItems as $item): ?>
                                <tr>
                                    <td class="px-md py-sm">
                                        <p class="font-label-md"><?= admin_html((string)$item['product_name']) ?></p>
                                        <?php if (trim((string)$item['variant_name']) !== ''): ?>
                                            <p class="text-xs text-on-surface-variant"><?= admin_html((string)$item['variant_name']) ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-md py-sm text-on-surface-variant"><?= admin_html((string)$item['sku']) ?></td>
                                    <td class="px-md py-sm text-right"><?= (int)$item['quantity'] ?></td>
                                    <td class="px-md py-sm text-right"><?= admin_html(admin_format_money((float)$item['unit_price'])) ?></td>
                                    <td class="px-md py-sm text-right font-label-md"><?= admin_html(admin_format_money((float)$item['total_price'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <div class="flex flex-wrap gap-md mb-lg">
            <div class="bg-white rounded-xl border border-outline-variant px-lg py-md min-w-[180px]">
                <p class="text-on-surface-variant text-sm">Total Orders</p>
                <p class="font-headline-md text-primary"><?= number_format((int)$stats['total_orders']) ?></p>
            </div>
            <div class="bg-white rounded-xl border border-outline-variant px-lg py-md min-w-[180px]">
                <p class="text-on-surface-variant text-sm">Pending/Processing</p>
                <p class="font-headline-md text-on-surface"><?= number_format((int)$stats['pending_orders']) ?></p>
            </div>
            <div class="bg-white rounded-xl border border-outline-variant px-lg py-md min-w-[180px]">
                <p class="text-on-surface-variant text-sm">Delivered</p>
                <p class="font-headline-md text-secondary"><?= number_format((int)$stats['delivered_orders']) ?></p>
            </div>
            <div class="bg-white rounded-xl border border-outline-variant px-lg py-md min-w-[180px]">
                <p class="text-on-surface-variant text-sm">Unpaid</p>
                <p class="font-headline-md text-tertiary"><?= number_format((int)$stats['unpaid_orders']) ?></p>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-end justify-between gap-md mb-xl">
            <div>
                <h2 class="font-headline-lg text-headline-lg text-primary tracking-tight">Orders</h2>
                <p class="font-body-md text-body-md text-on-surface-variant">Manage and track customer purchases.</p>
            </div>
            <form class="flex flex-wrap items-center gap-sm" method="get">
                <input name="q" type="hidden" value="<?= admin_html($queryFilters['q']) ?>"/>
                <input name="per_page" type="hidden" value="<?= (int)$queryFilters['per_page'] ?>"/>
                <input name="page" type="hidden" value="1"/>
                <div class="bg-surface-container rounded-full px-md py-sm flex items-center gap-sm border border-outline-variant">
                    <span class="font-label-md text-label-md text-on-surface-variant">Status:</span>
                    <select class="bg-transparent border-none focus:ring-0 font-label-md text-label-md text-primary cursor-pointer" name="status">
                        <option value="" <?= $queryFilters['status'] === '' ? 'selected' : '' ?>>All Status</option>
                        <?php foreach ($orderStatusOptions as $status): ?>
                            <option value="<?= admin_html($status) ?>" <?= $queryFilters['status'] === $status ? 'selected' : '' ?>><?= admin_html(ucfirst(str_replace('_', ' ', $status))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="bg-surface-container rounded-full px-md py-sm flex items-center gap-sm border border-outline-variant">
                    <span class="font-label-md text-label-md text-on-surface-variant">Payment:</span>
                    <select class="bg-transparent border-none focus:ring-0 font-label-md text-label-md text-primary cursor-pointer" name="payment_status">
                        <option value="" <?= $queryFilters['payment_status'] === '' ? 'selected' : '' ?>>All</option>
                        <?php foreach ($paymentStatusOptions as $status): ?>
                            <option value="<?= admin_html($status) ?>" <?= $queryFilters['payment_status'] === $status ? 'selected' : '' ?>><?= admin_html(ucfirst(str_replace('_', ' ', $status))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="bg-surface-container-high hover:bg-outline-variant transition-colors p-sm rounded-full flex items-center justify-center text-on-surface-variant" type="submit">
                    <span class="material-symbols-outlined">filter_list</span>
                </button>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-outline-variant">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant">
                        <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant">Order ID</th>
                        <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant">Date &amp; Time</th>
                        <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant">Customer</th>
                        <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant">Items</th>
                        <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant">Total</th>
                        <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant">Payment</th>
                        <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant">Status</th>
                        <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                    <?php if (count($orders) === 0): ?>
                        <tr>
                            <td class="px-lg py-lg text-on-surface-variant" colspan="8">No orders found for current filters.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($orders as $order): ?>
                        <?php
                        $orderId = (int)$order['id'];
                        $itemNames = is_array($order['item_names'] ?? null) ? $order['item_names'] : [];
                        $visibleItems = array_slice($itemNames, 0, 2);
                        $remainingItems = max(0, (int)($order['item_count'] ?? 0) - count($visibleItems));
                        $nextOrderStatus = nextOrderStatus((string)$order['order_status']);
                        $nextShippingStatus = nextShippingStatus((string)$order['shipping_status']);
                        $nextPaymentStatus = nextPaymentStatus((string)$order['payment_status']);
                        ?>
                        <tr class="order-row transition-colors duration-150">
                            <td class="px-lg py-lg font-label-md text-primary">#<?= admin_html((string)$order['order_number']) ?></td>
                            <td class="px-lg py-lg">
                                <div class="flex flex-col">
                                    <span class="font-body-md text-body-md"><?= admin_html(formatOrderDate((string)$order['order_datetime'])) ?></span>
                                    <span class="font-label-sm text-label-sm text-on-surface-variant"><?= admin_html(formatOrderTime((string)$order['order_datetime'])) ?></span>
                                </div>
                            </td>
                            <td class="px-lg py-lg">
                                <div class="flex items-center gap-sm">
                                    <div class="w-8 h-8 rounded-full bg-primary-fixed flex items-center justify-center text-on-primary-fixed font-label-sm"><?= admin_html(admin_initials((string)$order['customer_name'])) ?></div>
                                    <div class="flex flex-col">
                                        <span class="font-label-md text-label-md"><?= admin_html((string)$order['customer_name']) ?></span>
                                        <span class="font-label-sm text-label-sm text-on-surface-variant"><?= admin_html((string)$order['customer_email']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-lg py-lg">
                                <div class="flex items-center -space-x-2">
                                    <?php foreach ($visibleItems as $index => $itemName): ?>
                                        <?php
                                        $palette = itemBubblePalette($index);
                                        ?>
                                        <div class="w-8 h-8 rounded-full border-2 border-white flex items-center justify-center font-label-sm text-[10px] <?= $palette ?>">
                                            <?= admin_html(admin_initials($itemName)) ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if ($remainingItems > 0): ?>
                                        <div class="w-8 h-8 rounded-full border-2 border-white bg-surface-container flex items-center justify-center font-label-sm text-on-surface-variant text-[10px]">+<?= $remainingItems ?></div>
                                    <?php elseif (count($visibleItems) === 0): ?>
                                        <div class="text-on-surface-variant text-sm">0 items</div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-lg py-lg font-label-md text-label-md"><?= admin_html(admin_format_money((float)$order['total_amount'])) ?></td>
                            <td class="px-lg py-lg">
                                <span class="<?= admin_html(paymentStatusBadgeClass((string)$order['payment_status'])) ?>">
                                    <span class="material-symbols-outlined text-[14px]"><?= admin_html(paymentStatusIcon((string)$order['payment_status'])) ?></span>
                                    <?= admin_html(ucfirst(str_replace('_', ' ', (string)$order['payment_status']))) ?>
                                </span>
                            </td>
                            <td class="px-lg py-lg">
                                <span class="<?= admin_html(orderStatusBadgeClass((string)$order['order_status'])) ?>">
                                    <span class="material-symbols-outlined text-[14px]"><?= admin_html(orderStatusIcon((string)$order['order_status'])) ?></span>
                                    <?= admin_html(ucfirst(str_replace('_', ' ', (string)$order['order_status']))) ?>
                                </span>
                            </td>
                            <td class="px-lg py-lg text-center">
                                <button class="p-xs hover:bg-surface-container-high rounded-full transition-colors text-on-surface-variant" data-menu-order-id="<?= $orderId ?>" data-menu-next-order-status="<?= admin_html($nextOrderStatus) ?>" data-menu-next-shipping-status="<?= admin_html($nextShippingStatus) ?>" data-menu-next-payment-status="<?= admin_html($nextPaymentStatus) ?>" data-menu-order-query="<?= admin_html($baseQuery) ?>" onclick="toggleMenu(event, this)" type="button">
                                    <span class="material-symbols-outlined">more_vert</span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-lg py-md bg-surface-container-low flex items-center justify-between border-t border-outline-variant">
                <span class="font-label-sm text-label-sm text-on-surface-variant">
                    Showing <?= $totalRows === 0 ? 0 : (($currentPage - 1) * $queryFilters['per_page'] + 1) ?>
                    to <?= min($currentPage * $queryFilters['per_page'], $totalRows) ?>
                    of <?= number_format($totalRows) ?> orders
                </span>
                <div class="flex items-center gap-xs">
                    <?php if ($currentPage > 1): ?>
                        <a class="p-xs rounded-full hover:bg-surface-container-high text-on-surface-variant" href="manageOrders.php<?= admin_html(buildOrderFilterQuery(array_merge($queryFilters, ['page' => $currentPage - 1]))) ?>">
                            <span class="material-symbols-outlined">chevron_left</span>
                        </a>
                    <?php else: ?>
                        <button class="p-xs rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30" disabled>
                            <span class="material-symbols-outlined">chevron_left</span>
                        </button>
                    <?php endif; ?>

                    <?php
                    $pageStart = max(1, $currentPage - 1);
                    $pageEnd = min($totalPages, $pageStart + 2);
                    $pageStart = max(1, $pageEnd - 2);
                    for ($page = $pageStart; $page <= $pageEnd; $page++):
                        ?>
                        <a class="w-8 h-8 rounded-full flex items-center justify-center font-label-sm text-label-sm transition-colors <?= $page === $currentPage ? 'bg-primary text-on-primary' : 'hover:bg-surface-container-high' ?>" href="manageOrders.php<?= admin_html(buildOrderFilterQuery(array_merge($queryFilters, ['page' => $page]))) ?>">
                            <?= $page ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a class="p-xs rounded-full hover:bg-surface-container-high text-on-surface-variant" href="manageOrders.php<?= admin_html(buildOrderFilterQuery(array_merge($queryFilters, ['page' => $currentPage + 1]))) ?>">
                            <span class="material-symbols-outlined">chevron_right</span>
                        </a>
                    <?php else: ?>
                        <button class="p-xs rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30" disabled>
                            <span class="material-symbols-outlined">chevron_right</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="hidden fixed z-[100] bg-white rounded-lg shadow-xl border border-outline-variant w-56 py-sm flex flex-col scale-95 opacity-0 transition-all duration-200" id="actionMenu">
    <a class="flex items-center gap-sm px-md py-sm hover:bg-primary-container/20 transition-colors font-label-md text-label-md text-on-surface-variant" data-menu-view-link href="#">
        <span class="material-symbols-outlined">visibility</span>
        View Order Details
    </a>
    <form method="post">
        <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
        <input name="action" type="hidden" value="update_order_status"/>
        <input name="order_id" type="hidden" value="" data-menu-order-id-input/>
        <input name="order_status" type="hidden" value="" data-menu-order-status-input/>
        <input name="return_query" type="hidden" value="" data-menu-return-query-input/>
        <button class="w-full flex items-center gap-sm px-md py-sm hover:bg-primary-container/20 transition-colors font-label-md text-label-md text-on-surface-variant text-left" data-menu-order-status-button type="submit">
            <span class="material-symbols-outlined">edit_calendar</span>
            Update order status
        </button>
    </form>
    <form method="post">
        <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
        <input name="action" type="hidden" value="update_shipping_status"/>
        <input name="order_id" type="hidden" value="" data-menu-order-id-input/>
        <input name="shipping_status" type="hidden" value="" data-menu-shipping-status-input/>
        <input name="return_query" type="hidden" value="" data-menu-return-query-input/>
        <button class="w-full flex items-center gap-sm px-md py-sm hover:bg-primary-container/20 transition-colors font-label-md text-label-md text-on-surface-variant text-left" data-menu-shipping-status-button type="submit">
            <span class="material-symbols-outlined">local_shipping</span>
            Update shipping
        </button>
    </form>
    <form method="post">
        <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
        <input name="action" type="hidden" value="update_payment_status"/>
        <input name="order_id" type="hidden" value="" data-menu-order-id-input/>
        <input name="payment_status" type="hidden" value="" data-menu-payment-status-input/>
        <input name="return_query" type="hidden" value="" data-menu-return-query-input/>
        <button class="w-full flex items-center gap-sm px-md py-sm hover:bg-primary-container/20 transition-colors font-label-md text-label-md text-on-surface-variant text-left" data-menu-payment-status-button type="submit">
            <span class="material-symbols-outlined">payments</span>
            Update payment status
        </button>
    </form>
    <div class="h-[1px] bg-outline-variant my-xs mx-md"></div>
    <form method="post" onsubmit="return confirm('Cancel this order?');">
        <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
        <input name="action" type="hidden" value="cancel_order"/>
        <input name="order_id" type="hidden" value="" data-menu-order-id-input/>
        <input name="return_query" type="hidden" value="" data-menu-return-query-input/>
        <button class="w-full flex items-center gap-sm px-md py-sm hover:bg-error-container/20 transition-colors font-label-md text-label-md text-error text-left" type="submit">
            <span class="material-symbols-outlined">cancel</span>
            Cancel Order
        </button>
    </form>
</div>

<script>
    (() => {
        const menu = document.getElementById('actionMenu');
        if (!menu) {
            window.toggleMenu = () => {};
            return;
        }

        window.toggleMenu = function toggleMenu(event, button) {
            event.stopPropagation();
            const rect = button.getBoundingClientRect();
            const isOpen = !menu.classList.contains('hidden');
            const orderId = button.getAttribute('data-menu-order-id') || '';
            const nextOrderStatus = button.getAttribute('data-menu-next-order-status') || 'processing';
            const nextShippingStatus = button.getAttribute('data-menu-next-shipping-status') || 'shipped';
            const nextPaymentStatus = button.getAttribute('data-menu-next-payment-status') || 'paid';
            const orderQuery = button.getAttribute('data-menu-order-query') || '';

            menu.querySelectorAll('[data-menu-order-id-input]').forEach((input) => { input.value = orderId; });
            menu.querySelectorAll('[data-menu-order-status-input]').forEach((input) => { input.value = nextOrderStatus; });
            menu.querySelectorAll('[data-menu-shipping-status-input]').forEach((input) => { input.value = nextShippingStatus; });
            menu.querySelectorAll('[data-menu-payment-status-input]').forEach((input) => { input.value = nextPaymentStatus; });
            menu.querySelectorAll('[data-menu-return-query-input]').forEach((input) => { input.value = orderQuery; });

            const viewLink = menu.querySelector('[data-menu-view-link]');
            if (viewLink) {
                let href = 'manageOrders.php';
                if (orderQuery) {
                    href += orderQuery + '&view_id=' + encodeURIComponent(orderId);
                } else {
                    href += '?view_id=' + encodeURIComponent(orderId);
                }
                viewLink.setAttribute('href', href);
            }

            const orderStatusButton = menu.querySelector('[data-menu-order-status-button]');
            if (orderStatusButton) {
                orderStatusButton.innerHTML = '<span class="material-symbols-outlined">edit_calendar</span>Move to ' + formatStatusLabel(nextOrderStatus);
            }
            const shippingButton = menu.querySelector('[data-menu-shipping-status-button]');
            if (shippingButton) {
                shippingButton.innerHTML = '<span class="material-symbols-outlined">local_shipping</span>Shipping: ' + formatStatusLabel(nextShippingStatus);
            }
            const paymentButton = menu.querySelector('[data-menu-payment-status-button]');
            if (paymentButton) {
                paymentButton.innerHTML = '<span class="material-symbols-outlined">payments</span>Payment: ' + formatStatusLabel(nextPaymentStatus);
            }

            if (isOpen) {
                closeMenu();
                return;
            }

            menu.style.top = `${rect.bottom + 8}px`;
            menu.style.left = `${Math.max(8, rect.right - 224)}px`;
            menu.classList.remove('hidden');
            setTimeout(() => {
                menu.classList.remove('scale-95', 'opacity-0');
            }, 10);
        };

        function closeMenu() {
            menu.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                menu.classList.add('hidden');
            }, 200);
        }

        function formatStatusLabel(statusValue) {
            if (!statusValue) return '';
            return statusValue.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
        }

        if (typeof window.__adminOrdersMenuDocumentClickHandler === 'function') {
            document.removeEventListener('click', window.__adminOrdersMenuDocumentClickHandler);
        }
        const handleDocumentClick = (event) => {
            if (!(event.target instanceof Node) || !menu.contains(event.target)) {
                closeMenu();
            }
        };
        window.__adminOrdersMenuDocumentClickHandler = handleDocumentClick;
        document.addEventListener('click', handleDocumentClick);

        document.querySelectorAll('button').forEach((button) => {
            button.addEventListener('mousedown', () => button.classList.add('scale-95'));
            button.addEventListener('mouseup', () => button.classList.remove('scale-95'));
            button.addEventListener('mouseleave', () => button.classList.remove('scale-95'));
        });
    })();
</script>
</body>
</html>
<?php admin_page_cache_finish(); ?>

<?php

function handleOrderPostActions(
    PDO $pdo,
    array $admin,
    array $orderStatusOptions,
    array $paymentStatusOptions,
    array $shippingStatusOptions
): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    if (!admin_validate_csrf_token($csrfToken)) {
        setOrderFlash('error', 'Invalid form token. Please try again.');
        redirectManageOrders((string)($_POST['return_query'] ?? ''));
    }

    $action = trim((string)($_POST['action'] ?? ''));

    try {
        switch ($action) {
            case 'update_order_status':
                $orderId = max(0, (int)($_POST['order_id'] ?? 0));
                $status = trim((string)($_POST['order_status'] ?? ''));
                if (!in_array($status, $orderStatusOptions, true)) {
                    throw new InvalidArgumentException('Invalid order status.');
                }
                updateOrderField($pdo, $orderId, 'order_status', $status);
                setOrderFlash('success', 'Order status updated.');
                admin_log_activity($pdo, (int)$admin['id'], 'order.status', "Order #{$orderId} status => {$status}");
                redirectManageOrders((string)($_POST['return_query'] ?? ''));
                break;

            case 'update_payment_status':
                $orderId = max(0, (int)($_POST['order_id'] ?? 0));
                $status = trim((string)($_POST['payment_status'] ?? ''));
                if (!in_array($status, $paymentStatusOptions, true)) {
                    throw new InvalidArgumentException('Invalid payment status.');
                }
                updateOrderField($pdo, $orderId, 'payment_status', $status);
                setOrderFlash('success', 'Payment status updated.');
                admin_log_activity($pdo, (int)$admin['id'], 'order.payment', "Order #{$orderId} payment => {$status}");
                redirectManageOrders((string)($_POST['return_query'] ?? ''));
                break;

            case 'update_shipping_status':
                $orderId = max(0, (int)($_POST['order_id'] ?? 0));
                $status = trim((string)($_POST['shipping_status'] ?? ''));
                if (!in_array($status, $shippingStatusOptions, true)) {
                    throw new InvalidArgumentException('Invalid shipping status.');
                }
                updateOrderShippingStatus($pdo, $orderId, $status);
                setOrderFlash('success', 'Shipping status updated.');
                admin_log_activity($pdo, (int)$admin['id'], 'order.shipping', "Order #{$orderId} shipping => {$status}");
                redirectManageOrders((string)($_POST['return_query'] ?? ''));
                break;

            case 'cancel_order':
                $orderId = max(0, (int)($_POST['order_id'] ?? 0));
                if ($orderId <= 0) {
                    throw new InvalidArgumentException('Invalid order ID.');
                }
                $pdo->beginTransaction();
                try {
                    updateOrderField($pdo, $orderId, 'order_status', 'cancelled', false);
                    $existing = fetchOrderById($pdo, $orderId);
                    if (is_array($existing) && in_array((string)$existing['payment_status'], ['paid', 'partial_refund'], true)) {
                        updateOrderField($pdo, $orderId, 'payment_status', 'refunded', false);
                    }
                    $pdo->commit();
                } catch (Throwable $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $exception;
                }

                setOrderFlash('success', 'Order cancelled.');
                admin_log_activity($pdo, (int)$admin['id'], 'order.cancel', "Order #{$orderId} cancelled");
                redirectManageOrders((string)($_POST['return_query'] ?? ''));
                break;

            default:
                setOrderFlash('error', 'Unknown action.');
                redirectManageOrders((string)($_POST['return_query'] ?? ''));
        }
    } catch (Throwable $exception) {
        setOrderFlash('error', $exception->getMessage());
        redirectManageOrders((string)($_POST['return_query'] ?? ''));
    }
}

function updateOrderField(PDO $pdo, int $orderId, string $field, string $value, bool $touchTime = true): void
{
    if ($orderId <= 0) {
        throw new InvalidArgumentException('Invalid order ID.');
    }

    if (!in_array($field, ['order_status', 'payment_status', 'shipping_status'], true)) {
        throw new InvalidArgumentException('Invalid order field.');
    }

    $sql = "UPDATE orders SET {$field} = :value";
    if ($touchTime) {
        $sql .= ', updated_at = NOW()';
    }
    $sql .= ' WHERE id = :id';

    $statement = $pdo->prepare($sql);
    $statement->execute([
        ':value' => $value,
        ':id' => $orderId,
    ]);

    if ($statement->rowCount() === 0) {
        $exists = fetchOrderById($pdo, $orderId);
        if ($exists === null) {
            throw new RuntimeException('Order not found.');
        }
    }
}

function updateOrderShippingStatus(PDO $pdo, int $orderId, string $status): void
{
    $pdo->beginTransaction();
    try {
        updateOrderField($pdo, $orderId, 'shipping_status', $status, false);

        if (admin_table_exists($pdo, 'shipments')) {
            $shipmentStatus = in_array($status, ['preparing', 'shipped', 'in_transit', 'delivered', 'returned'], true)
                ? $status
                : 'preparing';

            $sql = 'UPDATE shipments SET status = :status, updated_at = NOW()';
            if (in_array($status, ['shipped', 'in_transit', 'delivered'], true)) {
                $sql .= ', shipped_at = COALESCE(shipped_at, NOW())';
            }
            if ($status === 'delivered') {
                $sql .= ', delivered_at = COALESCE(delivered_at, NOW())';
            }
            $sql .= ' WHERE order_id = :order_id';

            $statement = $pdo->prepare($sql);
            $statement->execute([
                ':status' => $shipmentStatus,
                ':order_id' => $orderId,
            ]);
        }

        $touch = $pdo->prepare('UPDATE orders SET updated_at = NOW() WHERE id = :id');
        $touch->execute([':id' => $orderId]);

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function fetchOrderStats(PDO $pdo): array
{
    $cacheKey = admin_cache_key('orders_stats', ['v' => 1]);
    $cached = null;
    if (admin_cache_fetch($cacheKey, $cached) && is_array($cached)) {
        return array_merge(defaultOrderStats(), $cached);
    }

    $stats = defaultOrderStats();

    $sql = "
        SELECT
            COUNT(*) AS total_orders,
            SUM(CASE WHEN order_status IN ('pending', 'confirmed', 'processing', 'shipped') THEN 1 ELSE 0 END) AS pending_orders,
            SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) AS delivered_orders,
            SUM(CASE WHEN payment_status = 'unpaid' THEN 1 ELSE 0 END) AS unpaid_orders
        FROM orders
    ";
    $row = $pdo->query($sql)->fetch();
    if (!is_array($row)) {
        return $stats;
    }

    $stats['total_orders'] = (int)($row['total_orders'] ?? 0);
    $stats['pending_orders'] = (int)($row['pending_orders'] ?? 0);
    $stats['delivered_orders'] = (int)($row['delivered_orders'] ?? 0);
    $stats['unpaid_orders'] = (int)($row['unpaid_orders'] ?? 0);

    admin_cache_store($cacheKey, $stats, ADMIN_PAGE_CACHE_TTL_SECONDS);
    return $stats;
}

function fetchOrderList(PDO $pdo, array $filters): array
{
    $cacheKey = admin_cache_key('orders_list', [
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

    $hasUsers = admin_table_exists($pdo, 'users');

    $params = [];
    $where = ['1=1'];

    if ($filters['q'] !== '') {
        if ($hasUsers) {
            $where[] = '(o.order_number LIKE :q OR u.name LIKE :q OR u.email LIKE :q)';
        } else {
            $where[] = 'o.order_number LIKE :q';
        }
        $params[':q'] = '%' . $filters['q'] . '%';
    }

    if ($filters['status'] !== '') {
        $where[] = 'o.order_status = :order_status';
        $params[':order_status'] = $filters['status'];
    }

    if ($filters['payment_status'] !== '') {
        $where[] = 'o.payment_status = :payment_status';
        $params[':payment_status'] = $filters['payment_status'];
    }

    $whereSql = implode(' AND ', $where);
    $usersJoin = $hasUsers ? 'LEFT JOIN users u ON u.id = o.user_id' : '';

    $countSql = "
        SELECT COUNT(*)
        FROM orders o
        {$usersJoin}
        WHERE {$whereSql}
    ";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $offset = max(0, ((int)$filters['page'] - 1) * (int)$filters['per_page']);

    $customerNameSql = $hasUsers
        ? "COALESCE(NULLIF(u.name, ''), NULLIF(u.email, ''), 'Guest')"
        : "'Guest'";
    $customerEmailSql = $hasUsers
        ? "COALESCE(NULLIF(u.email, ''), 'guest@example.com')"
        : "'guest@example.com'";

    $sql = "
        SELECT
            o.id,
            o.order_number,
            o.total_amount,
            o.order_status,
            o.payment_status,
            o.shipping_status,
            COALESCE(o.placed_at, o.created_at) AS order_datetime,
            {$customerNameSql} AS customer_name,
            {$customerEmailSql} AS customer_email
        FROM orders o
        {$usersJoin}
        WHERE {$whereSql}
        ORDER BY COALESCE(o.placed_at, o.created_at) DESC, o.id DESC
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
    $orders = is_array($rows) ? $rows : [];

    hydrateOrderItemSummary($pdo, $orders);
    $result = ['rows' => $orders, 'total' => $total];
    admin_cache_store($cacheKey, $result, 15);
    return $result;
}

function hydrateOrderItemSummary(PDO $pdo, array &$orders): void
{
    foreach ($orders as &$order) {
        $order['item_count'] = 0;
        $order['item_names'] = [];
    }
    unset($order);

    if (count($orders) === 0 || !admin_table_exists($pdo, 'order_items')) {
        return;
    }

    $orderIds = [];
    foreach ($orders as $order) {
        $id = (int)($order['id'] ?? 0);
        if ($id > 0) {
            $orderIds[] = $id;
        }
    }
    $orderIds = array_values(array_unique($orderIds));
    if (count($orderIds) === 0) {
        return;
    }

    $placeholders = implode(', ', array_fill(0, count($orderIds), '?'));
    $sql = "
        SELECT
            order_id,
            COALESCE(SUM(quantity), 0) AS item_count,
            GROUP_CONCAT(COALESCE(NULLIF(product_name, ''), 'Item') ORDER BY id ASC SEPARATOR '||') AS item_names
        FROM order_items
        WHERE order_id IN ({$placeholders})
        GROUP BY order_id
    ";

    $statement = $pdo->prepare($sql);
    foreach ($orderIds as $index => $orderId) {
        $statement->bindValue($index + 1, $orderId, PDO::PARAM_INT);
    }
    $statement->execute();
    $summaryRows = $statement->fetchAll();

    $summaryMap = [];
    if (is_array($summaryRows)) {
        foreach ($summaryRows as $summaryRow) {
            $itemNamesRaw = (string)($summaryRow['item_names'] ?? '');
            $summaryMap[(int)$summaryRow['order_id']] = [
                'item_count' => (int)($summaryRow['item_count'] ?? 0),
                'item_names' => $itemNamesRaw === '' ? [] : explode('||', $itemNamesRaw),
            ];
        }
    }

    foreach ($orders as &$order) {
        $orderId = (int)($order['id'] ?? 0);
        if (!isset($summaryMap[$orderId])) {
            continue;
        }
        $order['item_count'] = $summaryMap[$orderId]['item_count'];
        $order['item_names'] = $summaryMap[$orderId]['item_names'];
    }
    unset($order);
}

function fetchOrderById(PDO $pdo, int $orderId): ?array
{
    if ($orderId <= 0 || !admin_table_exists($pdo, 'orders')) {
        return null;
    }

    $cacheKey = admin_cache_key('orders_detail', [
        'order_id' => $orderId,
        'v' => 1,
    ]);
    $cached = null;
    if (admin_cache_fetch($cacheKey, $cached)) {
        return is_array($cached) ? $cached : null;
    }

    $hasUsers = admin_table_exists($pdo, 'users');
    $usersJoin = $hasUsers ? 'LEFT JOIN users u ON u.id = o.user_id' : '';
    $customerNameSql = $hasUsers
        ? "COALESCE(NULLIF(u.name, ''), NULLIF(u.email, ''), 'Guest')"
        : "'Guest'";
    $customerEmailSql = $hasUsers
        ? "COALESCE(NULLIF(u.email, ''), 'guest@example.com')"
        : "'guest@example.com'";

    $sql = "
        SELECT
            o.*,
            COALESCE(o.placed_at, o.created_at) AS order_datetime,
            {$customerNameSql} AS customer_name,
            {$customerEmailSql} AS customer_email
        FROM orders o
        {$usersJoin}
        WHERE o.id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $orderId]);
    $row = $stmt->fetch();
    $result = is_array($row) ? $row : null;
    admin_cache_store($cacheKey, $result, ADMIN_PAGE_CACHE_TTL_SECONDS);
    return $result;
}

function fetchOrderItemsByOrderId(PDO $pdo, int $orderId): array
{
    if ($orderId <= 0 || !admin_table_exists($pdo, 'order_items')) {
        return [];
    }

    $cacheKey = admin_cache_key('orders_items', [
        'order_id' => $orderId,
        'v' => 1,
    ]);
    $cached = null;
    if (admin_cache_fetch($cacheKey, $cached) && is_array($cached)) {
        return $cached;
    }

    $stmt = $pdo->prepare(
        "SELECT product_name, variant_name, sku, quantity, unit_price, total_price
         FROM order_items
         WHERE order_id = :order_id
         ORDER BY id ASC"
    );
    $stmt->execute([':order_id' => $orderId]);
    $rows = $stmt->fetchAll();
    $result = is_array($rows) ? $rows : [];
    admin_cache_store($cacheKey, $result, ADMIN_PAGE_CACHE_TTL_SECONDS);
    return $result;
}

function setOrderFlash(string $type, string $message): void
{
    $_SESSION['admin_orders_flash'] = ['type' => $type, 'message' => $message];
}

function pullOrderFlash(): ?array
{
    if (!isset($_SESSION['admin_orders_flash']) || !is_array($_SESSION['admin_orders_flash'])) {
        return null;
    }

    $flash = $_SESSION['admin_orders_flash'];
    unset($_SESSION['admin_orders_flash']);
    return $flash;
}

function redirectManageOrders(string $returnQuery): never
{
    $safeQuery = sanitizeOrderReturnQuery($returnQuery);
    header('Location: manageOrders.php' . $safeQuery);
    exit;
}

function sanitizeOrderReturnQuery(string $query): string
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

function buildOrderFilterQuery(array $filters): string
{
    $query = http_build_query([
        'q' => (string)$filters['q'],
        'status' => (string)$filters['status'],
        'payment_status' => (string)$filters['payment_status'],
        'per_page' => (int)$filters['per_page'],
        'page' => (int)$filters['page'],
    ]);
    return $query === '' ? '' : '?' . $query;
}

function defaultOrderStats(): array
{
    return [
        'total_orders' => 0,
        'pending_orders' => 0,
        'delivered_orders' => 0,
        'unpaid_orders' => 0,
    ];
}

function admin_initials(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return 'A';
    }
    $parts = preg_split('/\s+/', $name);
    if (!is_array($parts) || count($parts) === 0) {
        return strtoupper(substr($name, 0, 1));
    }
    $first = strtoupper(substr((string)$parts[0], 0, 1));
    $second = '';
    if (count($parts) > 1) {
        $second = strtoupper(substr((string)$parts[count($parts) - 1], 0, 1));
    }
    return $first . ($second !== '' ? $second : '');
}

function formatOrderDate(string $datetime): string
{
    if ($datetime === '') {
        return '—';
    }
    try {
        $dt = new DateTimeImmutable($datetime);
        return $dt->format('M d, Y');
    } catch (Throwable) {
        return $datetime;
    }
}

function formatOrderTime(string $datetime): string
{
    if ($datetime === '') {
        return '—';
    }
    try {
        $dt = new DateTimeImmutable($datetime);
        return $dt->format('h:i A');
    } catch (Throwable) {
        return '';
    }
}

function itemBubblePalette(int $index): string
{
    $palette = [
        'bg-tertiary-container text-on-tertiary-container',
        'bg-primary-fixed text-on-primary-fixed',
        'bg-secondary-fixed text-on-secondary-fixed',
    ];
    return $palette[$index % count($palette)];
}

function orderStatusBadgeClass(string $status): string
{
    return match ($status) {
        'delivered' => 'px-md py-xs rounded-full font-label-sm text-label-sm inline-flex items-center gap-xs bg-secondary-container text-on-secondary-container',
        'shipped' => 'px-md py-xs rounded-full font-label-sm text-label-sm inline-flex items-center gap-xs bg-secondary-fixed text-on-secondary-fixed',
        'processing', 'confirmed' => 'px-md py-xs rounded-full font-label-sm text-label-sm inline-flex items-center gap-xs bg-primary-container text-on-primary-container',
        'pending' => 'px-md py-xs rounded-full font-label-sm text-label-sm inline-flex items-center gap-xs bg-surface-container-high text-on-surface-variant',
        'cancelled', 'refunded' => 'px-md py-xs rounded-full font-label-sm text-label-sm inline-flex items-center gap-xs bg-error-container text-on-error-container',
        default => 'px-md py-xs rounded-full font-label-sm text-label-sm inline-flex items-center gap-xs bg-surface-container text-on-surface-variant',
    };
}

function paymentStatusBadgeClass(string $status): string
{
    return match ($status) {
        'paid' => 'px-md py-xs rounded-full font-label-sm text-label-sm inline-flex items-center gap-xs bg-secondary-container text-on-secondary-container',
        'partial_refund' => 'px-md py-xs rounded-full font-label-sm text-label-sm inline-flex items-center gap-xs bg-tertiary-container text-on-tertiary-container',
        'unpaid' => 'px-md py-xs rounded-full font-label-sm text-label-sm inline-flex items-center gap-xs bg-surface-container-high text-on-surface-variant',
        'failed', 'refunded' => 'px-md py-xs rounded-full font-label-sm text-label-sm inline-flex items-center gap-xs bg-error-container text-on-error-container',
        default => 'px-md py-xs rounded-full font-label-sm text-label-sm inline-flex items-center gap-xs bg-surface-container text-on-surface-variant',
    };
}

function orderStatusIcon(string $status): string
{
    return match ($status) {
        'delivered' => 'check_circle',
        'shipped' => 'local_shipping',
        'processing', 'confirmed' => 'autorenew',
        'pending' => 'pending',
        'cancelled' => 'cancel',
        'refunded' => 'currency_exchange',
        default => 'help',
    };
}

function paymentStatusIcon(string $status): string
{
    return match ($status) {
        'paid' => 'check_circle',
        'partial_refund' => 'currency_exchange',
        'unpaid' => 'schedule',
        'failed' => 'error',
        'refunded' => 'undo',
        default => 'help',
    };
}

function nextOrderStatus(string $current): string
{
    return match ($current) {
        'pending', 'confirmed' => 'processing',
        'processing' => 'shipped',
        'shipped' => 'delivered',
        default => $current,
    };
}

function nextShippingStatus(string $current): string
{
    return match ($current) {
        'not_shipped' => 'preparing',
        'preparing' => 'shipped',
        'shipped' => 'in_transit',
        'in_transit' => 'delivered',
        default => $current,
    };
}

function nextPaymentStatus(string $current): string
{
    return match ($current) {
        'unpaid', 'failed' => 'paid',
        'paid' => 'partial_refund',
        default => $current,
    };
}



