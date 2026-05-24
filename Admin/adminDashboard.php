<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_layout.php';

admin_start_session();
$admin = admin_require_auth('adminLogin.php');
admin_page_cache_start($admin, 'dashboard', ADMIN_PAGE_CACHE_TTL_SECONDS);

$metrics = [
    'total_sales' => 0.0,
    'today_sales' => 0.0,
    'total_orders' => 0,
    'pending_orders' => 0,
    'total_customers' => 0,
    'low_stock_variants' => 0,
    'active_products' => 0,
];
$recentOrders = [];
$orderStatus = [
    'completed' => 0,
    'pending' => 0,
    'cancelled' => 0,
    'total' => 0,
];
$revenueTrend = [
    'labels' => ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
    'values' => [0.0, 0.0, 0.0, 0.0],
    'max' => 0.0,
    'has_data' => false,
];
$dbError = null;
$searchQuery = trim((string)($_GET['q'] ?? ''));

try {
    $pdo = admin_db();
    admin_ensure_tables($pdo);
    $metrics = admin_fetch_dashboard_metrics($pdo);
    $recentOrders = admin_fetch_recent_orders($pdo, 8, $searchQuery);
    $orderStatus = admin_fetch_order_status_summary($pdo);
    $revenueTrend = admin_fetch_revenue_trend($pdo, 4);
} catch (Throwable $exception) {
    $dbError = $exception->getMessage();
}

$revenueLabels = $revenueTrend['labels'];
$revenueValues = $revenueTrend['values'];
$revenueHasData = (bool)($revenueTrend['has_data'] ?? false);

if (count($revenueLabels) === 0 || count($revenueValues) === 0 || count($revenueLabels) !== count($revenueValues)) {
    $revenueLabels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
    $revenueValues = [0.0, 0.0, 0.0, 0.0];
    $revenueHasData = false;
}

$chartPointCount = count($revenueValues);
$chartMax = max(1.0, (float)($revenueTrend['max'] ?? 0.0));
$lineParts = [];

foreach ($revenueValues as $index => $value) {
    $x = $chartPointCount === 1 ? 400.0 : (800.0 * $index / ($chartPointCount - 1));
    $normalized = max(0.0, min(1.0, ((float)$value / $chartMax)));
    $y = 180.0 - ($normalized * 130.0);

    if (!$revenueHasData) {
        $y = 180.0;
    }

    $lineParts[] = ($index === 0 ? 'M' : ' L') . sprintf('%.2f,%.2f', $x, $y);
}

$revenueLinePath = implode('', $lineParts);
$revenueAreaPath = $revenueLinePath . ' L 800,200 L 0,200 Z';
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>LuvShop Admin Dashboard</title>
    <?php admin_render_critical_css(); ?>
    <link href="<?= admin_html(admin_material_symbols_href()) ?>" rel="stylesheet"/>
    <?php $adminCssHref = admin_css_href(); ?>
<?php if ($adminCssHref !== null): ?>
    <link href="<?= admin_html($adminCssHref) ?>" rel="stylesheet"/>
<?php endif; ?>
<style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #fbf9f8;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .soft-shadow {
            box-shadow: 0 10px 30px -5px rgba(120, 85, 94, 0.08);
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #ffd1dc;
            border-radius: 10px;
        }
    </style>
</head>
<body class="bg-background text-on-surface">
    <?php
    admin_render_sidebar($admin, 'dashboard');
    ?>

    <main class="ml-64 min-h-screen" id="app-main">
        <?php
        admin_render_header($admin, [
            'search_action' => 'adminDashboard.php',
            'search_method' => 'get',
            'search_name' => 'q',
            'search_value' => $searchQuery,
            'search_placeholder' => 'Search orders, customers...',
        ]);
        ?>

        <div class="p-lg space-y-lg">
            <?php if ($dbError !== null): ?>
                <section class="rounded-lg border border-red-200 bg-error-container px-md py-sm text-red-800">
                    Database error: <?= admin_html($dbError) ?>
                </section>
            <?php endif; ?>

            <section class="flex justify-between items-end">
                <div>
                    <h2 class="font-headline-lg text-headline-lg text-primary">Good Morning, Manager!</h2>
                    <p class="font-body-md text-body-md text-on-surface-variant">Here's what's happening at LuvShop today.</p>
                </div>
                <div class="flex gap-sm">
                    <button class="bg-surface-container-lowest border border-outline-variant px-lg py-2 rounded-lg font-label-md text-label-md hover:bg-surface-container-high transition-colors">Export Data</button>
                    <button class="bg-primary text-white px-lg py-2 rounded-lg font-label-md text-label-md shadow-lg active:scale-95 transition-transform">Create Promotion</button>
                </div>
            </section>

            <section class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-md">
                <div class="bg-surface-container-lowest p-md rounded-lg soft-shadow border border-surface-variant flex flex-col gap-xs">
                    <span class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider">Total Sales</span>
                    <div class="flex items-center justify-between">
                        <span class="font-headline-md text-headline-md text-on-surface"><?= admin_html(admin_format_money((float)$metrics['total_sales'])) ?></span>
                    </div>
                </div>
                <div class="bg-surface-container-lowest p-md rounded-lg soft-shadow border border-surface-variant flex flex-col gap-xs">
                    <span class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider">Today's Sales</span>
                    <div class="flex items-center justify-between">
                        <span class="font-headline-md text-headline-md text-on-surface"><?= admin_html(admin_format_money((float)$metrics['today_sales'])) ?></span>
                    </div>
                </div>
                <div class="bg-surface-container-lowest p-md rounded-lg soft-shadow border border-surface-variant flex flex-col gap-xs">
                    <span class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider">Total Orders</span>
                    <span class="font-headline-md text-headline-md text-on-surface"><?= number_format((int)$metrics['total_orders']) ?></span>
                </div>
                <div class="bg-tertiary-container p-md rounded-lg soft-shadow border border-outline-variant flex flex-col gap-xs">
                    <span class="text-label-sm font-label-sm text-on-surface uppercase tracking-wider">Pending Orders</span>
                    <span class="font-headline-md text-headline-md text-on-surface"><?= number_format((int)$metrics['pending_orders']) ?></span>
                </div>
                <div class="bg-surface-container-lowest p-md rounded-lg soft-shadow border border-surface-variant flex flex-col gap-xs">
                    <span class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider">Total Customers</span>
                    <span class="font-headline-md text-headline-md text-on-surface"><?= number_format((int)$metrics['total_customers']) ?></span>
                </div>
                <div class="bg-error-container p-md rounded-lg soft-shadow border border-outline-variant flex flex-col gap-xs">
                    <span class="text-label-sm font-label-sm text-on-surface uppercase tracking-wider">Low Stock</span>
                    <div class="flex items-center gap-2">
                        <span class="font-headline-md text-headline-md text-on-surface"><?= number_format((int)$metrics['low_stock_variants']) ?></span>
                        <span class="material-symbols-outlined text-error" style="font-variation-settings: 'FILL' 1;">warning</span>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 lg:grid-cols-3 gap-lg">
                <div class="lg:col-span-2 bg-surface-container-lowest p-lg rounded-lg soft-shadow border border-surface-variant">
                    <div class="flex justify-between items-center mb-lg">
                        <h3 class="font-headline-md text-headline-md text-on-surface">Revenue Trend</h3>
                        <select class="bg-surface-container-low border-none rounded-lg text-label-md py-1 px-4 focus:ring-2 focus:ring-primary-container">
                            <option>Last 30 Days</option>
                            <option>Last 6 Months</option>
                        </select>
                    </div>
                    <div class="h-64 relative flex items-end justify-between gap-4">
                        <?php if (!$revenueHasData): ?>
                            <div class="absolute inset-0 flex items-center justify-center opacity-40">
                                <span class="font-label-md text-label-md text-on-surface-variant">No paid order data yet</span>
                            </div>
                        <?php endif; ?>
                        <svg class="w-full h-full" preserveaspectratio="none" viewbox="0 0 800 200">
                            <path d="<?= admin_html($revenueLinePath) ?>" fill="none" stroke="#78555e" stroke-linecap="round" stroke-width="4"></path>
                            <path d="<?= admin_html($revenueAreaPath) ?>" fill="rgba(255, 209, 220, 0.3)"></path>
                        </svg>
                        <div class="absolute bottom-0 w-full flex justify-between text-label-sm text-outline px-2">
                            <?php foreach ($revenueLabels as $label): ?>
                                <span><?= admin_html($label) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="bg-surface-container-lowest p-lg rounded-lg soft-shadow border border-surface-variant flex flex-col">
                    <h3 class="font-headline-md text-headline-md text-on-surface mb-lg">Order Status</h3>
                    <div class="flex-1 flex flex-col justify-center items-center">
                        <div class="relative w-48 h-48 rounded-full border-[16px] border-surface-container flex items-center justify-center mb-lg">
                            <div class="absolute inset-0 rounded-full border-[16px] border-secondary border-t-transparent border-l-transparent rotate-45"></div>
                            <div class="text-center">
                                <span class="block font-headline-md text-headline-md text-on-surface"><?= number_format((int)$orderStatus['total']) ?></span>
                                <span class="text-label-sm font-label-sm text-outline">Total</span>
                            </div>
                        </div>
                        <?php if ((int)$orderStatus['total'] === 0): ?>
                            <p class="text-label-sm text-outline mb-sm">No order status data yet</p>
                        <?php endif; ?>
                        <div class="w-full space-y-sm">
                            <div class="flex justify-between items-center px-md py-2 bg-secondary-container rounded-lg">
                                <span class="font-label-md text-label-md text-on-surface">Completed</span>
                                <span class="font-bold text-on-surface"><?= number_format((int)$orderStatus['completed']) ?></span>
                            </div>
                            <div class="flex justify-between items-center px-md py-2 bg-tertiary-container rounded-lg">
                                <span class="font-label-md text-label-md text-on-surface">Pending</span>
                                <span class="font-bold text-on-surface"><?= number_format((int)$orderStatus['pending']) ?></span>
                            </div>
                            <div class="flex justify-between items-center px-md py-2 bg-error-container rounded-lg">
                                <span class="font-label-md text-label-md text-on-surface">Cancelled</span>
                                <span class="font-bold text-on-surface"><?= number_format((int)$orderStatus['cancelled']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 lg:grid-cols-3 gap-lg">
                <div class="lg:col-span-2 bg-surface-container-lowest p-lg rounded-lg soft-shadow border border-surface-variant overflow-hidden">
                    <div class="flex justify-between items-center mb-lg">
                        <h3 class="font-headline-md text-headline-md text-on-surface">Recent Orders</h3>
                        <a class="text-primary font-label-md text-label-md hover:underline" href="manageOrders.php">View All</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-label-sm font-label-sm text-outline border-b border-surface-variant">
                                    <th class="pb-4 font-bold">Order ID</th>
                                    <th class="pb-4 font-bold">Customer</th>
                                    <th class="pb-4 font-bold text-right">Amount</th>
                                    <th class="pb-4 font-bold text-center">Status</th>
                                    <th class="pb-4"></th>
                                </tr>
                            </thead>
                            <tbody class="text-label-md font-label-md text-on-surface divide-y divide-surface-variant/50">
                                <?php if (count($recentOrders) === 0): ?>
                                    <tr><td class="py-4" colspan="5"><?= $searchQuery !== '' ? 'No matching orders found.' : 'No orders found.' ?></td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <?php $status = (string)($order['order_status'] ?? 'unknown'); ?>
                                        <tr class="group hover:bg-surface-container-low transition-colors">
                                            <td class="py-4">#<?= admin_html((string)($order['order_number'] ?? '-')) ?></td>
                                            <td class="py-4"><?= admin_html((string)($order['customer_name'] ?? 'Guest')) ?></td>
                                            <td class="py-4 text-right font-bold"><?= admin_html(admin_format_money((float)($order['total_amount'] ?? 0))) ?></td>
                                            <td class="py-4 text-center">
                                                <span class="px-3 py-1 text-label-sm font-label-sm rounded-full <?= admin_status_badge_class($status) ?>"><?= admin_html($status) ?></span>
                                            </td>
                                            <td class="py-4 text-right">
                                                <button class="material-symbols-outlined text-outline hover:text-primary">more_vert</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="bg-surface-container-lowest p-lg rounded-lg soft-shadow border border-surface-variant">
                    <h3 class="font-headline-md text-headline-md text-on-surface mb-lg">Quick Stats</h3>
                    <div class="space-y-md">
                        <div class="flex items-center justify-between bg-surface-container-low rounded-lg px-md py-sm">
                            <span class="font-label-md text-label-md text-on-surface">Active Products</span>
                            <span class="font-bold"><?= number_format((int)$metrics['active_products']) ?></span>
                        </div>
                        <div class="flex items-center justify-between bg-surface-container-low rounded-lg px-md py-sm">
                            <span class="font-label-md text-label-md text-on-surface">Total Customers</span>
                            <span class="font-bold"><?= number_format((int)$metrics['total_customers']) ?></span>
                        </div>
                        <div class="flex items-center justify-between bg-surface-container-low rounded-lg px-md py-sm">
                            <span class="font-label-md text-label-md text-on-surface">Low Stock Variants</span>
                            <span class="font-bold"><?= number_format((int)$metrics['low_stock_variants']) ?></span>
                        </div>
                    </div>
                    <a class="w-full mt-lg py-3 rounded-lg bg-surface-container-low text-primary font-label-md text-label-md hover:bg-primary-container transition-colors font-bold text-center block" href="manageProducts.php">
                        Analyze Performance
                    </a>
                </div>
            </section>
        </div>
    </main>

    <script>
        (() => {
            document.querySelectorAll('.soft-shadow').forEach((card) => {
                card.addEventListener('mouseenter', () => {
                    card.style.transition = 'all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                });
            });
        })();
    </script>
</body>
</html>
<?php admin_page_cache_finish(); ?>


