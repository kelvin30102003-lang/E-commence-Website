<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_layout.php';

admin_start_session();
$admin = admin_require_auth('adminLogin.php');

$pdo = admin_db();
admin_ensure_tables($pdo);
$csrfToken = admin_bootstrap_csrf_token();

$paymentStatusOptions = ['unpaid', 'paid', 'failed', 'refunded', 'partial_refund'];

$queryFilters = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
    'method' => trim((string)($_GET['method'] ?? '')),
    'date_from' => trim((string)($_GET['date_from'] ?? '')),
    'date_to' => trim((string)($_GET['date_to'] ?? '')),
    'page' => max(1, (int)($_GET['page'] ?? 1)),
    'per_page' => max(5, min(100, (int)($_GET['per_page'] ?? 10))),
];

if (!isValidDateValue($queryFilters['date_from'])) {
    $queryFilters['date_from'] = '';
}
if (!isValidDateValue($queryFilters['date_to'])) {
    $queryFilters['date_to'] = '';
}

$ordersTableExists = admin_table_exists($pdo, 'orders');
$usersTableExists = admin_table_exists($pdo, 'users');
$orderColumns = $ordersTableExists ? fetchTableColumns($pdo, 'orders') : [];
$usersColumns = $usersTableExists ? fetchTableColumns($pdo, 'users') : [];

handlePaymentPostActions($pdo, $admin, $paymentStatusOptions, $ordersTableExists, $orderColumns);
handlePaymentCsvExport($pdo, $queryFilters, $ordersTableExists, $orderColumns, $usersTableExists, $usersColumns);

$flash = pullPaymentFlash();
$stats = $ordersTableExists ? fetchPaymentStats($pdo, $orderColumns) : defaultPaymentStats();
$methodOptions = $ordersTableExists ? fetchPaymentMethodOptions($pdo, $orderColumns) : [];
$weeklyVolume = $ordersTableExists ? fetchWeeklyPaymentVolume($pdo, $orderColumns) : defaultWeeklyPaymentVolume();
$alerts = $ordersTableExists ? fetchPaymentAlerts($pdo, $orderColumns) : [];

$listData = $ordersTableExists
    ? fetchPaymentList($pdo, $queryFilters, $orderColumns, $usersTableExists, $usersColumns)
    : ['rows' => [], 'total' => 0];
$payments = $listData['rows'];
$totalRows = (int)$listData['total'];
$totalPages = max(1, (int)ceil($totalRows / $queryFilters['per_page']));
$currentPage = min($queryFilters['page'], $totalPages);

if ($currentPage !== $queryFilters['page'] && $ordersTableExists) {
    $queryFilters['page'] = $currentPage;
    $listData = fetchPaymentList($pdo, $queryFilters, $orderColumns, $usersTableExists, $usersColumns);
    $payments = $listData['rows'];
}

$baseQuery = buildPaymentFilterQuery($queryFilters);
$clearQuery = buildPaymentFilterQuery(array_merge($queryFilters, [
    'status' => '',
    'method' => '',
    'date_from' => '',
    'date_to' => '',
    'page' => 1,
]));
$exportQuery = '?' . http_build_query([
    'q' => $queryFilters['q'],
    'status' => $queryFilters['status'],
    'method' => $queryFilters['method'],
    'date_from' => $queryFilters['date_from'],
    'date_to' => $queryFilters['date_to'],
    'per_page' => 2000,
    'page' => 1,
    'export' => 'csv',
]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Payment Management | LuvShop Admin</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&amp;family=Plus+Jakarta+Sans:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <style>
        body {
            background-color: #fbf9f8;
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .spring-animation {
            transition: transform 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .spring-animation:active {
            transform: scale(0.95);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.35);
        }
    </style>
</head>
<body class="text-slate-800">
<?php
admin_render_sidebar($admin, 'payments');
?>
<main class="ml-64 min-h-screen" id="app-main">
    <?php
    admin_render_header($admin, [
        'search_action' => 'managePayments.php',
        'search_method' => 'get',
        'search_name' => 'q',
        'search_value' => (string)$queryFilters['q'],
        'search_placeholder' => 'Search transaction or order ID...',
        'search_hidden' => [
            'status' => (string)$queryFilters['status'],
            'method' => (string)$queryFilters['method'],
            'date_from' => (string)$queryFilters['date_from'],
            'date_to' => (string)$queryFilters['date_to'],
            'per_page' => (string)$queryFilters['per_page'],
            'page' => '1',
        ],
    ]);
    ?>

    <div class="p-6 max-w-[1280px] mx-auto space-y-6">
        <?php if (!$ordersTableExists): ?>
            <div class="rounded-lg border border-red-200 bg-red-50 px-6 py-4 text-red-700">
                Orders table not found. Create `orders` table first to manage payments.
            </div>
        <?php endif; ?>

        <?php if ($flash !== null): ?>
            <div class="rounded-lg border px-6 py-4 <?= $flash['type'] === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700' ?>">
                <?= admin_html($flash['message']) ?>
            </div>
        <?php endif; ?>

        <section class="flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <h2 class="text-3xl font-bold text-[#78555e]">Payment Management</h2>
                <p class="text-slate-500 mt-1">Monitor and manage all customer transactions globally.</p>
            </div>
            <div class="flex gap-3">
                <a class="flex items-center gap-2 px-6 py-3 bg-[#b2f2bb] text-[#145129] rounded-full font-semibold spring-animation" href="managePayments.php<?= admin_html($exportQuery) ?>">
                    <span class="material-symbols-outlined text-sm">download</span>
                    Export Report
                </a>
                <a class="flex items-center gap-2 px-6 py-3 bg-[#78555e] text-white rounded-full font-semibold spring-animation shadow-md" href="manageOrders.php">
                    <span class="material-symbols-outlined text-sm">receipt_long</span>
                    Open Orders
                </a>
            </div>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 transition-transform hover:-translate-y-1">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-[#ffd1dc] rounded-xl flex items-center justify-center text-[#78555e]">
                        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">payments</span>
                    </div>
                    <span class="text-[#2f6a3f] text-xs font-semibold bg-[#b2f2bb]/30 px-2 py-1 rounded-full">
                        <?= number_format((float)$stats['success_rate'], 1) ?>%
                    </span>
                </div>
                <p class="text-slate-500 text-sm">Total Revenue</p>
                <h3 class="text-2xl font-semibold mt-1"><?= admin_html(admin_format_money((float)$stats['total_revenue'])) ?></h3>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 transition-transform hover:-translate-y-1">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-[#e9ddab] rounded-xl flex items-center justify-center text-[#665f36]">
                        <span class="material-symbols-outlined">hourglass_empty</span>
                    </div>
                    <span class="text-slate-500 text-xs font-semibold bg-slate-100 px-2 py-1 rounded-full">Manual</span>
                </div>
                <p class="text-slate-500 text-sm">Pending Verification</p>
                <h3 class="text-2xl font-semibold mt-1"><?= number_format((int)$stats['pending_count']) ?></h3>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 transition-transform hover:-translate-y-1">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-[#b2f2bb] rounded-xl flex items-center justify-center text-[#2f6a3f]">
                        <span class="material-symbols-outlined">assignment_return</span>
                    </div>
                    <span class="text-red-600 text-xs font-semibold bg-red-100 px-2 py-1 rounded-full">
                        <?= number_format((float)$stats['refund_rate'], 2) ?>%
                    </span>
                </div>
                <p class="text-slate-500 text-sm">Refund Rate</p>
                <h3 class="text-2xl font-semibold mt-1"><?= number_format((int)$stats['refunded_count']) ?> Refunded</h3>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 transition-transform hover:-translate-y-1">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center text-red-600">
                        <span class="material-symbols-outlined">cancel</span>
                    </div>
                    <span class="text-slate-500 text-xs font-semibold bg-slate-100 px-2 py-1 rounded-full">Global</span>
                </div>
                <p class="text-slate-500 text-sm">Failed Payments</p>
                <h3 class="text-2xl font-semibold mt-1"><?= number_format((int)$stats['failed_count']) ?></h3>
            </div>
        </section>

        <form class="bg-slate-100 p-4 rounded-lg flex flex-wrap items-center gap-4" method="get">
            <input name="q" type="hidden" value="<?= admin_html((string)$queryFilters['q']) ?>"/>
            <input name="page" type="hidden" value="1"/>
            <input name="per_page" type="hidden" value="<?= (int)$queryFilters['per_page'] ?>"/>

            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-slate-500">Status:</span>
                <select class="bg-white border-none rounded-full py-2 px-4 focus:ring-2 focus:ring-[#ffd1dc]" name="status">
                    <option value="" <?= $queryFilters['status'] === '' ? 'selected' : '' ?>>All Status</option>
                    <?php foreach ($paymentStatusOptions as $statusOption): ?>
                        <option value="<?= admin_html($statusOption) ?>" <?= $queryFilters['status'] === $statusOption ? 'selected' : '' ?>><?= admin_html(ucfirst(str_replace('_', ' ', $statusOption))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-slate-500">Method:</span>
                <select class="bg-white border-none rounded-full py-2 px-4 focus:ring-2 focus:ring-[#ffd1dc]" name="method">
                    <option value="" <?= $queryFilters['method'] === '' ? 'selected' : '' ?>>Any Method</option>
                    <?php foreach ($methodOptions as $methodOption): ?>
                        <option value="<?= admin_html($methodOption) ?>" <?= $queryFilters['method'] === $methodOption ? 'selected' : '' ?>><?= admin_html($methodOption) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-slate-500">Date Range:</span>
                <div class="flex items-center bg-white rounded-full px-4 py-2 gap-3">
                    <input class="bg-transparent border-none text-sm focus:ring-0" name="date_from" type="date" value="<?= admin_html((string)$queryFilters['date_from']) ?>"/>
                    <span class="text-slate-400">to</span>
                    <input class="bg-transparent border-none text-sm focus:ring-0" name="date_to" type="date" value="<?= admin_html((string)$queryFilters['date_to']) ?>"/>
                </div>
            </div>

            <button class="ml-auto px-4 py-2 bg-[#78555e] text-white rounded-full font-semibold spring-animation" type="submit">Apply</button>
            <a class="text-[#78555e] font-semibold px-4 py-2 hover:bg-[#ffd1dc] rounded-full transition-colors" href="managePayments.php<?= admin_html($clearQuery) ?>">Clear Filters</a>
        </form>

        <section class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-sm text-slate-500 uppercase tracking-wider">Transaction ID</th>
                        <th class="px-6 py-4 text-sm text-slate-500 uppercase tracking-wider">Customer / Order</th>
                        <th class="px-6 py-4 text-sm text-slate-500 uppercase tracking-wider">Date &amp; Time</th>
                        <th class="px-6 py-4 text-sm text-slate-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-4 text-sm text-slate-500 uppercase tracking-wider">Method</th>
                        <th class="px-6 py-4 text-sm text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-sm text-slate-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                    <?php if (count($payments) === 0): ?>
                        <tr>
                            <td class="px-6 py-5 text-slate-500" colspan="7">No payment records found for current filters.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($payments as $payment): ?>
                        <?php
                        $status = (string)$payment['payment_status'];
                        $canMarkPaid = in_array($status, ['unpaid', 'failed'], true);
                        $canRefund = in_array($status, ['paid', 'partial_refund'], true);
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-5">
                                <span class="font-semibold text-[#78555e]">#<?= admin_html((string)$payment['transaction_id']) ?></span>
                            </td>
                            <td class="px-6 py-5">
                                <div>
                                    <p class="text-sm text-slate-800"><?= admin_html((string)$payment['customer_name']) ?></p>
                                    <p class="text-xs text-slate-500">#<?= admin_html((string)$payment['order_number']) ?></p>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <p class="text-sm text-slate-800"><?= admin_html(formatPaymentDate((string)$payment['payment_datetime'])) ?></p>
                                <p class="text-xs text-slate-500"><?= admin_html(formatPaymentTime((string)$payment['payment_datetime'])) ?></p>
                            </td>
                            <td class="px-6 py-5 font-semibold"><?= admin_html(admin_format_money((float)$payment['amount'])) ?></td>
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-slate-400"><?= admin_html(paymentMethodIcon((string)$payment['payment_method'])) ?></span>
                                    <span class="text-sm"><?= admin_html((string)$payment['payment_method']) ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <span class="<?= admin_html(paymentStatusBadgeClass($status)) ?>">
                                    <span class="w-2 h-2 rounded-full <?= admin_html(paymentStatusDotClass($status)) ?>"></span>
                                    <?= admin_html(ucfirst(str_replace('_', ' ', $status))) ?>
                                </span>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a class="p-2 hover:bg-slate-100 rounded-full transition-colors" href="manageOrders.php?view_id=<?= (int)$payment['order_id'] ?>" title="View Details">
                                        <span class="material-symbols-outlined text-slate-500">visibility</span>
                                    </a>

                                    <?php if ($canRefund): ?>
                                        <form method="post" onsubmit="return confirm('Process refund for this payment?');">
                                            <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
                                            <input name="action" type="hidden" value="update_payment_status"/>
                                            <input name="order_id" type="hidden" value="<?= (int)$payment['order_id'] ?>"/>
                                            <input name="payment_status" type="hidden" value="refunded"/>
                                            <input name="return_query" type="hidden" value="<?= admin_html($baseQuery) ?>"/>
                                            <button class="p-2 hover:bg-red-100 text-red-600 rounded-full transition-colors" title="Process Refund" type="submit">
                                                <span class="material-symbols-outlined">keyboard_return</span>
                                            </button>
                                        </form>
                                    <?php elseif ($canMarkPaid): ?>
                                        <form method="post">
                                            <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
                                            <input name="action" type="hidden" value="update_payment_status"/>
                                            <input name="order_id" type="hidden" value="<?= (int)$payment['order_id'] ?>"/>
                                            <input name="payment_status" type="hidden" value="paid"/>
                                            <input name="return_query" type="hidden" value="<?= admin_html($baseQuery) ?>"/>
                                            <button class="px-4 py-2 rounded-full text-sm font-semibold bg-[#ffd1dc] text-[#7a5761] hover:bg-[#78555e] hover:text-white transition-colors spring-animation" type="submit">
                                                Confirm Payment
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="bg-slate-100 px-6 py-4 flex items-center justify-between">
                <p class="text-xs text-slate-500">
                    Showing <?= $totalRows === 0 ? 0 : (($currentPage - 1) * $queryFilters['per_page'] + 1) ?>
                    to <?= min($currentPage * $queryFilters['per_page'], $totalRows) ?> of <?= number_format($totalRows) ?> entries
                </p>
                <div class="flex gap-2">
                    <?php if ($currentPage > 1): ?>
                        <a class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-slate-200 transition-colors" href="managePayments.php<?= admin_html(buildPaymentFilterQuery(array_merge($queryFilters, ['page' => $currentPage - 1]))) ?>">
                            <span class="material-symbols-outlined">chevron_left</span>
                        </a>
                    <?php else: ?>
                        <button class="w-10 h-10 flex items-center justify-center rounded-full opacity-40" disabled>
                            <span class="material-symbols-outlined">chevron_left</span>
                        </button>
                    <?php endif; ?>

                    <?php
                    $pageStart = max(1, $currentPage - 1);
                    $pageEnd = min($totalPages, $pageStart + 2);
                    $pageStart = max(1, $pageEnd - 2);
                    for ($page = $pageStart; $page <= $pageEnd; $page++):
                    ?>
                        <a class="w-10 h-10 flex items-center justify-center rounded-full text-sm font-semibold <?= $page === $currentPage ? 'bg-[#78555e] text-white' : 'hover:bg-slate-200' ?>" href="managePayments.php<?= admin_html(buildPaymentFilterQuery(array_merge($queryFilters, ['page' => $page]))) ?>">
                            <?= $page ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-slate-200 transition-colors" href="managePayments.php<?= admin_html(buildPaymentFilterQuery(array_merge($queryFilters, ['page' => $currentPage + 1]))) ?>">
                            <span class="material-symbols-outlined">chevron_right</span>
                        </a>
                    <?php else: ?>
                        <button class="w-10 h-10 flex items-center justify-center rounded-full opacity-40" disabled>
                            <span class="material-symbols-outlined">chevron_right</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 glass-card p-6 rounded-lg shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <h4 class="text-xl font-semibold text-slate-800">Weekly Transaction Volume</h4>
                    <div class="flex gap-4 text-xs text-slate-500">
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-[#78555e]"></span> Successful</span>
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-500"></span> Failed</span>
                    </div>
                </div>

                <div class="h-64 flex items-end justify-between gap-3 px-2">
                    <?php foreach ($weeklyVolume['rows'] as $day): ?>
                        <?php
                        $maxValue = max(1, (int)$weeklyVolume['max']);
                        $successHeight = (int)round(((int)$day['successful'] / $maxValue) * 100);
                        $failedHeight = (int)round(((int)$day['failed'] / $maxValue) * 100);
                        ?>
                        <div class="flex-1 flex flex-col items-center gap-2">
                            <div class="w-full h-48 flex items-end gap-1">
                                <div class="w-1/2 bg-[#ffd1dc] rounded-t-lg" style="height: <?= max(4, $successHeight) ?>%"></div>
                                <div class="w-1/2 bg-red-200 rounded-t-lg" style="height: <?= max(4, $failedHeight) ?>%"></div>
                            </div>
                            <span class="text-xs text-slate-500"><?= admin_html((string)$day['label']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bg-slate-100 p-6 rounded-lg shadow-sm flex flex-col">
                <h4 class="text-xl font-semibold text-slate-800 mb-6">Recent Alerts</h4>
                <div class="flex flex-col gap-4">
                    <?php if (count($alerts) === 0): ?>
                        <div class="flex gap-4 p-4 bg-white rounded-lg border-l-4 border-[#2f6a3f]">
                            <span class="material-symbols-outlined text-[#2f6a3f]">check_circle</span>
                            <div>
                                <p class="font-semibold text-slate-800">No critical alerts</p>
                                <p class="text-xs text-slate-500">Payment system is running normally.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($alerts as $alert): ?>
                        <div class="flex gap-4 p-4 bg-white rounded-lg border-l-4 <?= admin_html((string)$alert['border_class']) ?>">
                            <span class="material-symbols-outlined <?= admin_html((string)$alert['icon_class']) ?>"><?= admin_html((string)$alert['icon']) ?></span>
                            <div>
                                <p class="font-semibold text-slate-800"><?= admin_html((string)$alert['title']) ?></p>
                                <p class="text-xs text-slate-500"><?= admin_html((string)$alert['detail']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a class="mt-auto w-full py-3 text-[#78555e] font-semibold border border-[#78555e]/20 rounded-full hover:bg-[#ffd1dc] transition-colors spring-animation text-center" href="activityLogs.php">
                    View All Activity
                </a>
            </div>
        </section>
    </div>
</main>
</body>
</html>

<?php

function handlePaymentCsvExport(
    PDO $pdo,
    array $filters,
    bool $ordersTableExists,
    array $orderColumns,
    bool $usersTableExists,
    array $usersColumns
): void {
    if (trim((string)($_GET['export'] ?? '')) !== 'csv' || !$ordersTableExists) {
        return;
    }

    $exportFilters = $filters;
    $exportFilters['page'] = 1;
    $exportFilters['per_page'] = 5000;

    $rows = fetchPaymentList($pdo, $exportFilters, $orderColumns, $usersTableExists, $usersColumns)['rows'];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="payments-' . date('Ymd-His') . '.csv"');

    $out = fopen('php://output', 'wb');
    if ($out === false) {
        exit;
    }

    fputcsv($out, ['Transaction ID', 'Order Number', 'Customer Name', 'Customer Email', 'Date Time', 'Amount', 'Method', 'Status']);
    foreach ($rows as $row) {
        fputcsv($out, [
            (string)$row['transaction_id'],
            (string)$row['order_number'],
            (string)$row['customer_name'],
            (string)$row['customer_email'],
            (string)$row['payment_datetime'],
            (string)$row['amount'],
            (string)$row['payment_method'],
            (string)$row['payment_status'],
        ]);
    }

    fclose($out);
    exit;
}

function handlePaymentPostActions(
    PDO $pdo,
    array $admin,
    array $statusOptions,
    bool $ordersTableExists,
    array $orderColumns
): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    if (!admin_validate_csrf_token($csrfToken)) {
        setPaymentFlash('error', 'Invalid form token. Please refresh and try again.');
        redirectManagePayments((string)($_POST['return_query'] ?? ''));
    }

    if (!$ordersTableExists) {
        setPaymentFlash('error', 'Orders table is not available.');
        redirectManagePayments((string)($_POST['return_query'] ?? ''));
    }

    $action = trim((string)($_POST['action'] ?? ''));
    try {
        if ($action !== 'update_payment_status') {
            throw new InvalidArgumentException('Unknown action.');
        }
        if (!tableHasColumn($orderColumns, 'payment_status')) {
            throw new RuntimeException('`payment_status` column was not found in orders table.');
        }

        $orderId = max(0, (int)($_POST['order_id'] ?? 0));
        if ($orderId <= 0) {
            throw new InvalidArgumentException('Invalid order ID.');
        }

        $status = trim((string)($_POST['payment_status'] ?? ''));
        if (!in_array($status, $statusOptions, true)) {
            throw new InvalidArgumentException('Invalid payment status.');
        }

        $sql = 'UPDATE orders SET payment_status = :payment_status';
        if (tableHasColumn($orderColumns, 'updated_at')) {
            $sql .= ', updated_at = NOW()';
        }
        $sql .= ' WHERE id = :id';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':payment_status' => $status,
            ':id' => $orderId,
        ]);

        admin_log_activity($pdo, (int)($admin['id'] ?? 0), 'payment.status', 'Updated payment status for order #' . $orderId . ' to ' . $status);
        setPaymentFlash('success', 'Payment status updated.');
        redirectManagePayments((string)($_POST['return_query'] ?? ''));
    } catch (Throwable $exception) {
        setPaymentFlash('error', $exception->getMessage());
        redirectManagePayments((string)($_POST['return_query'] ?? ''));
    }
}

function fetchPaymentStats(PDO $pdo, array $orderColumns): array
{
    $stats = defaultPaymentStats();
    $statusExpr = resolvePaymentStatusExpression($orderColumns, 'o');
    $amountExpr = resolveAmountExpression($orderColumns, 'o');

    $sql = "
        SELECT
            COUNT(*) AS total_transactions,
            SUM(CASE WHEN {$statusExpr} IN ('paid', 'partial_refund') THEN {$amountExpr} ELSE 0 END) AS total_revenue,
            SUM(CASE WHEN {$statusExpr} = 'unpaid' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN {$statusExpr} = 'refunded' THEN 1 ELSE 0 END) AS refunded_count,
            SUM(CASE WHEN {$statusExpr} = 'failed' THEN 1 ELSE 0 END) AS failed_count
        FROM orders o
    ";
    $row = $pdo->query($sql)->fetch();
    if (!is_array($row)) {
        return $stats;
    }

    $stats['total_transactions'] = (int)($row['total_transactions'] ?? 0);
    $stats['total_revenue'] = (float)($row['total_revenue'] ?? 0);
    $stats['pending_count'] = (int)($row['pending_count'] ?? 0);
    $stats['refunded_count'] = (int)($row['refunded_count'] ?? 0);
    $stats['failed_count'] = (int)($row['failed_count'] ?? 0);

    if ($stats['total_transactions'] > 0) {
        $successfulCount = $stats['total_transactions'] - $stats['pending_count'] - $stats['failed_count'];
        $stats['success_rate'] = max(0.0, ($successfulCount / $stats['total_transactions']) * 100);
        $stats['refund_rate'] = ($stats['refunded_count'] / $stats['total_transactions']) * 100;
    }

    return $stats;
}

function fetchPaymentList(
    PDO $pdo,
    array $filters,
    array $orderColumns,
    bool $usersTableExists,
    array $usersColumns
): array {
    $params = [];
    $where = ['1 = 1'];

    $statusExpr = resolvePaymentStatusExpression($orderColumns, 'o');
    $methodExpr = resolvePaymentMethodExpression($orderColumns, 'o');
    $dateExpr = resolveOrderDateExpression($orderColumns, 'o');
    $transactionExpr = resolveTransactionIdExpression($orderColumns, 'o');
    $orderNumberExpr = resolveOrderNumberExpression($orderColumns, 'o');
    $amountExpr = resolveAmountExpression($orderColumns, 'o');

    $methodColumn = resolvePaymentMethodColumn($orderColumns);
    $canJoinUsers = $usersTableExists
        && tableHasColumn($orderColumns, 'user_id')
        && tableHasColumn($usersColumns, 'id')
        && tableHasColumn($usersColumns, 'email');
    $usersJoin = $canJoinUsers ? 'LEFT JOIN users u ON u.id = o.user_id' : '';

    $customerNameExpr = $canJoinUsers
        ? "COALESCE(NULLIF(u.name, ''), NULLIF(u.email, ''), 'Guest')"
        : (tableHasColumn($orderColumns, 'customer_name')
            ? "COALESCE(NULLIF(o.customer_name, ''), 'Guest')"
            : "'Guest'");
    $customerEmailExpr = $canJoinUsers
        ? "COALESCE(NULLIF(u.email, ''), 'guest@example.com')"
        : (tableHasColumn($orderColumns, 'customer_email')
            ? "COALESCE(NULLIF(o.customer_email, ''), 'guest@example.com')"
            : "'guest@example.com'");

    if ($filters['q'] !== '') {
        $where[] = $canJoinUsers
            ? "({$transactionExpr} LIKE :q OR {$orderNumberExpr} LIKE :q OR u.name LIKE :q OR u.email LIKE :q)"
            : "({$transactionExpr} LIKE :q OR {$orderNumberExpr} LIKE :q)";
        $params[':q'] = '%' . $filters['q'] . '%';
    }

    if ($filters['status'] !== '' && tableHasColumn($orderColumns, 'payment_status')) {
        $where[] = 'o.payment_status = :payment_status';
        $params[':payment_status'] = $filters['status'];
    }

    if ($filters['method'] !== '' && $methodColumn !== null) {
        $where[] = "o.{$methodColumn} = :method";
        $params[':method'] = $filters['method'];
    }

    if ($filters['date_from'] !== '') {
        $where[] = "DATE({$dateExpr}) >= :date_from";
        $params[':date_from'] = $filters['date_from'];
    }
    if ($filters['date_to'] !== '') {
        $where[] = "DATE({$dateExpr}) <= :date_to";
        $params[':date_to'] = $filters['date_to'];
    }

    $whereSql = implode(' AND ', $where);
    $countSql = "SELECT COUNT(*) FROM orders o {$usersJoin} WHERE {$whereSql}";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $offset = max(0, ((int)$filters['page'] - 1) * (int)$filters['per_page']);
    $sql = "
        SELECT
            o.id AS order_id,
            {$transactionExpr} AS transaction_id,
            {$orderNumberExpr} AS order_number,
            {$dateExpr} AS payment_datetime,
            {$amountExpr} AS amount,
            {$methodExpr} AS payment_method,
            {$statusExpr} AS payment_status,
            {$customerNameExpr} AS customer_name,
            {$customerEmailExpr} AS customer_email
        FROM orders o
        {$usersJoin}
        WHERE {$whereSql}
        ORDER BY {$dateExpr} DESC, o.id DESC
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
    return ['rows' => is_array($rows) ? $rows : [], 'total' => $total];
}

function fetchPaymentMethodOptions(PDO $pdo, array $orderColumns): array
{
    $methodColumn = resolvePaymentMethodColumn($orderColumns);
    if ($methodColumn === null) {
        return [];
    }

    $rows = $pdo->query("SELECT DISTINCT {$methodColumn} AS method FROM orders WHERE {$methodColumn} IS NOT NULL AND {$methodColumn} <> '' ORDER BY {$methodColumn}")->fetchAll();
    if (!is_array($rows)) {
        return [];
    }

    $methods = [];
    foreach ($rows as $row) {
        $method = trim((string)($row['method'] ?? ''));
        if ($method !== '') {
            $methods[] = $method;
        }
    }

    return $methods;
}

function fetchWeeklyPaymentVolume(PDO $pdo, array $orderColumns): array
{
    $result = defaultWeeklyPaymentVolume();
    $statusExpr = resolvePaymentStatusExpression($orderColumns, 'o');
    $dateExpr = resolveOrderDateExpression($orderColumns, 'o');

    $rows = $pdo->query("
        SELECT
            DATE({$dateExpr}) AS day_date,
            SUM(CASE WHEN {$statusExpr} IN ('paid', 'partial_refund') THEN 1 ELSE 0 END) AS successful,
            SUM(CASE WHEN {$statusExpr} = 'failed' THEN 1 ELSE 0 END) AS failed
        FROM orders o
        WHERE DATE({$dateExpr}) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE({$dateExpr})
    ")->fetchAll();

    $map = [];
    if (is_array($rows)) {
        foreach ($rows as $row) {
            $map[(string)($row['day_date'] ?? '')] = [
                'successful' => (int)($row['successful'] ?? 0),
                'failed' => (int)($row['failed'] ?? 0),
            ];
        }
    }

    $out = [];
    $max = 1;
    for ($i = 6; $i >= 0; $i--) {
        $date = (new DateTimeImmutable('today'))->modify('-' . $i . ' day');
        $key = $date->format('Y-m-d');
        $successful = (int)($map[$key]['successful'] ?? 0);
        $failed = (int)($map[$key]['failed'] ?? 0);
        $out[] = [
            'label' => $date->format('D'),
            'successful' => $successful,
            'failed' => $failed,
        ];
        $max = max($max, $successful, $failed);
    }

    return ['rows' => $out, 'max' => $max];
}

function fetchPaymentAlerts(PDO $pdo, array $orderColumns): array
{
    $alerts = [];
    $statusExpr = resolvePaymentStatusExpression($orderColumns, 'o');
    $amountExpr = resolveAmountExpression($orderColumns, 'o');
    $orderNumberExpr = resolveOrderNumberExpression($orderColumns, 'o');
    $dateExpr = resolveOrderDateExpression($orderColumns, 'o');

    $largeRefund = $pdo->query("
        SELECT {$orderNumberExpr} AS order_number, {$amountExpr} AS amount
        FROM orders o
        WHERE {$statusExpr} = 'refunded' AND {$amountExpr} >= 500
        ORDER BY {$dateExpr} DESC
        LIMIT 1
    ")->fetch();
    if (is_array($largeRefund)) {
        $alerts[] = [
            'icon' => 'warning',
            'title' => 'Large Refund Request',
            'detail' => '#' . (string)$largeRefund['order_number'] . ' refunded ' . admin_format_money((float)$largeRefund['amount']) . '.',
            'border_class' => 'border-[#665f36]',
            'icon_class' => 'text-[#665f36]',
        ];
    }

    $failedToday = (int)$pdo->query("
        SELECT COUNT(*)
        FROM orders o
        WHERE {$statusExpr} = 'failed'
          AND {$dateExpr} >= DATE_SUB(NOW(), INTERVAL 1 DAY)
    ")->fetchColumn();
    if ($failedToday > 0) {
        $alerts[] = [
            'icon' => 'error',
            'title' => 'Failed Payments Detected',
            'detail' => number_format($failedToday) . ' failed payment(s) in the last 24 hours.',
            'border_class' => 'border-red-500',
            'icon_class' => 'text-red-600',
        ];
    }

    $pendingCount = (int)$pdo->query("
        SELECT COUNT(*)
        FROM orders o
        WHERE {$statusExpr} = 'unpaid'
    ")->fetchColumn();
    if ($pendingCount > 0) {
        $alerts[] = [
            'icon' => 'hourglass_top',
            'title' => 'Pending Verification Queue',
            'detail' => number_format($pendingCount) . ' unpaid transaction(s) need confirmation.',
            'border_class' => 'border-[#2f6a3f]',
            'icon_class' => 'text-[#2f6a3f]',
        ];
    }

    return $alerts;
}

function resolvePaymentStatusExpression(array $orderColumns, string $alias): string
{
    return tableHasColumn($orderColumns, 'payment_status') ? "{$alias}.payment_status" : "'unpaid'";
}

function resolveAmountExpression(array $orderColumns, string $alias): string
{
    return tableHasColumn($orderColumns, 'total_amount') ? "COALESCE({$alias}.total_amount, 0)" : '0';
}

function resolveOrderDateExpression(array $orderColumns, string $alias): string
{
    $hasPlaced = tableHasColumn($orderColumns, 'placed_at');
    $hasCreated = tableHasColumn($orderColumns, 'created_at');
    if ($hasPlaced && $hasCreated) {
        return "COALESCE({$alias}.placed_at, {$alias}.created_at)";
    }
    if ($hasPlaced) {
        return "{$alias}.placed_at";
    }
    if ($hasCreated) {
        return "{$alias}.created_at";
    }
    return 'NOW()';
}

function resolvePaymentMethodColumn(array $orderColumns): ?string
{
    foreach (['payment_method', 'payment_provider', 'payment_gateway', 'payment_channel'] as $candidate) {
        if (tableHasColumn($orderColumns, $candidate)) {
            return $candidate;
        }
    }
    return null;
}

function resolvePaymentMethodExpression(array $orderColumns, string $alias): string
{
    $column = resolvePaymentMethodColumn($orderColumns);
    if ($column === null) {
        return "'Unknown'";
    }
    return "COALESCE(NULLIF({$alias}.{$column}, ''), 'Unknown')";
}

function resolveTransactionIdExpression(array $orderColumns, string $alias): string
{
    foreach (['transaction_id', 'payment_reference', 'reference_number'] as $candidate) {
        if (tableHasColumn($orderColumns, $candidate)) {
            return "COALESCE(NULLIF({$alias}.{$candidate}, ''), CONCAT('TXN-', LPAD({$alias}.id, 6, '0')))";
        }
    }
    return "CONCAT('TXN-', LPAD({$alias}.id, 6, '0'))";
}

function resolveOrderNumberExpression(array $orderColumns, string $alias): string
{
    if (tableHasColumn($orderColumns, 'order_number')) {
        return "COALESCE(NULLIF({$alias}.order_number, ''), CONCAT('ORD-', LPAD({$alias}.id, 6, '0')))";
    }
    return "CONCAT('ORD-', LPAD({$alias}.id, 6, '0'))";
}

function fetchTableColumns(PDO $pdo, string $table): array
{
    $stmt = $pdo->prepare(
        'SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table_name'
    );
    $stmt->execute([':table_name' => $table]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!is_array($rows)) {
        return [];
    }

    $columns = [];
    foreach ($rows as $name) {
        $columns[strtolower((string)$name)] = true;
    }
    return $columns;
}

function tableHasColumn(array $columns, string $name): bool
{
    return isset($columns[strtolower($name)]);
}

function defaultPaymentStats(): array
{
    return [
        'total_transactions' => 0,
        'total_revenue' => 0.0,
        'pending_count' => 0,
        'refunded_count' => 0,
        'failed_count' => 0,
        'success_rate' => 0.0,
        'refund_rate' => 0.0,
    ];
}

function defaultWeeklyPaymentVolume(): array
{
    $rows = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = (new DateTimeImmutable('today'))->modify('-' . $i . ' day');
        $rows[] = ['label' => $d->format('D'), 'successful' => 0, 'failed' => 0];
    }
    return ['rows' => $rows, 'max' => 1];
}

function paymentStatusBadgeClass(string $status): string
{
    return match ($status) {
        'paid' => 'bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-1',
        'unpaid' => 'bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-1',
        'failed' => 'bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-1',
        'refunded' => 'bg-slate-200 text-slate-600 px-3 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-1',
        default => 'bg-pink-100 text-pink-700 px-3 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-1',
    };
}

function paymentStatusDotClass(string $status): string
{
    return match ($status) {
        'paid' => 'bg-green-600',
        'unpaid' => 'bg-yellow-600',
        'failed' => 'bg-red-600',
        'refunded' => 'bg-slate-500',
        default => 'bg-pink-600',
    };
}

function paymentMethodIcon(string $method): string
{
    $method = strtolower(trim($method));
    if (str_contains($method, 'card')) {
        return 'credit_card';
    }
    if (str_contains($method, 'bank') || str_contains($method, 'transfer')) {
        return 'account_balance';
    }
    if (str_contains($method, 'paypal') || str_contains($method, 'wallet')) {
        return 'account_balance_wallet';
    }
    return 'payments';
}

function formatPaymentDate(string $datetime): string
{
    $datetime = trim($datetime);
    if ($datetime === '') {
        return 'N/A';
    }
    try {
        return (new DateTimeImmutable($datetime))->format('M d, Y');
    } catch (Throwable) {
        return $datetime;
    }
}

function formatPaymentTime(string $datetime): string
{
    $datetime = trim($datetime);
    if ($datetime === '') {
        return 'N/A';
    }
    try {
        return (new DateTimeImmutable($datetime))->format('H:i A');
    } catch (Throwable) {
        return $datetime;
    }
}

function setPaymentFlash(string $type, string $message): void
{
    $_SESSION['admin_payments_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function pullPaymentFlash(): ?array
{
    if (!isset($_SESSION['admin_payments_flash']) || !is_array($_SESSION['admin_payments_flash'])) {
        return null;
    }

    $flash = $_SESSION['admin_payments_flash'];
    unset($_SESSION['admin_payments_flash']);
    return $flash;
}

function redirectManagePayments(string $returnQuery): never
{
    header('Location: managePayments.php' . sanitizePaymentReturnQuery($returnQuery));
    exit;
}

function sanitizePaymentReturnQuery(string $query): string
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
        'method' => trim((string)($parsed['method'] ?? '')),
        'date_from' => trim((string)($parsed['date_from'] ?? '')),
        'date_to' => trim((string)($parsed['date_to'] ?? '')),
        'per_page' => max(5, min(100, (int)($parsed['per_page'] ?? 10))),
        'page' => max(1, (int)($parsed['page'] ?? 1)),
    ];
    if (!isValidDateValue($safe['date_from'])) {
        $safe['date_from'] = '';
    }
    if (!isValidDateValue($safe['date_to'])) {
        $safe['date_to'] = '';
    }
    return buildPaymentFilterQuery($safe);
}

function buildPaymentFilterQuery(array $filters): string
{
    $query = http_build_query([
        'q' => (string)$filters['q'],
        'status' => (string)$filters['status'],
        'method' => (string)$filters['method'],
        'date_from' => (string)$filters['date_from'],
        'date_to' => (string)$filters['date_to'],
        'per_page' => (int)$filters['per_page'],
        'page' => (int)$filters['page'],
    ]);
    return $query === '' ? '' : '?' . $query;
}

function isValidDateValue(string $value): bool
{
    $value = trim($value);
    if ($value === '') {
        return false;
    }
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    return $dt !== false && $dt->format('Y-m-d') === $value;
}

