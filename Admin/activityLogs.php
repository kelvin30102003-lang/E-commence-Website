<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_layout.php';

admin_start_session();
$admin = admin_require_auth('adminLogin.php');

$logs = [];
$dbError = null;

try {
    $pdo = admin_db();
    admin_ensure_tables($pdo);
    $logs = admin_fetch_activity_logs($pdo, 150);
} catch (Throwable $exception) {
    $dbError = $exception->getMessage();
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Activity Logs | LuvShop Admin</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script defer src="../Assect/js/site-ajax.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">
    <?php admin_render_sidebar($admin, 'logs'); ?>

    <main class="ml-64 min-h-screen" id="app-main">
        <?php
        admin_render_header($admin, [
            'search_action' => 'activityLogs.php',
            'search_method' => 'get',
            'search_name' => 'q',
            'search_value' => '',
            'search_placeholder' => 'Search logs...',
        ]);
        ?>

        <section class="p-6 md:p-8 space-y-6">
            <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900">Admin Activity Logs</h2>
                    <p class="text-sm text-slate-500">Recent backend actions for admin accounts.</p>
                </div>
                <div class="text-sm text-slate-500">
                    Signed in as <span class="font-medium text-slate-700"><?= admin_html($admin['email']) ?></span>
                </div>
            </header>

            <?php if ($dbError !== null): ?>
                <section class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    Database error: <?= admin_html($dbError) ?>
                </section>
            <?php endif; ?>

            <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <header class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <h3 class="font-semibold text-slate-900">Latest Logs</h3>
                    <span class="text-sm text-slate-500"><?= count($logs) ?> rows</span>
                </header>

                <?php if (count($logs) === 0): ?>
                    <div class="px-5 py-10 text-center text-slate-500 text-sm">No admin activity logged yet.</div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="text-left px-5 py-3 font-medium">Time</th>
                                    <th class="text-left px-5 py-3 font-medium">Admin</th>
                                    <th class="text-left px-5 py-3 font-medium">Action</th>
                                    <th class="text-left px-5 py-3 font-medium">Details</th>
                                    <th class="text-left px-5 py-3 font-medium">IP</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($logs as $log): ?>
                                    <?php $adminLabel = trim((string)($log['admin_name'] ?? '')) !== '' ? (string)$log['admin_name'] : ((string)($log['admin_email'] ?? 'System')); ?>
                                    <tr>
                                        <td class="px-5 py-3 text-slate-500"><?= admin_html((string)($log['created_at'] ?? '-')) ?></td>
                                        <td class="px-5 py-3 text-slate-800 font-medium"><?= admin_html($adminLabel) ?></td>
                                        <td class="px-5 py-3"><code class="text-xs bg-slate-100 px-2 py-1 rounded"><?= admin_html((string)($log['action'] ?? '')) ?></code></td>
                                        <td class="px-5 py-3 text-slate-600"><?= admin_html((string)($log['details'] ?? '')) ?></td>
                                        <td class="px-5 py-3 text-slate-500"><?= admin_html((string)($log['ip_address'] ?? '-')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </section>
    </main>
</body>
</html>
