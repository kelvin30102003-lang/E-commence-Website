<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_layout.php';

admin_start_session();
$admin = admin_require_auth('adminLogin.php');

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Users | LuvShop Admin</title>
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
    <?php admin_render_sidebar($admin, 'users'); ?>

    <main class="ml-64 min-h-screen" id="app-main">
        <?php
        admin_render_header($admin, [
            'search_action' => 'manageUsers.php',
            'search_method' => 'get',
            'search_name' => 'q',
            'search_value' => trim((string)($_GET['q'] ?? '')),
            'search_placeholder' => 'Search Customers...',
        ]);
        ?>

        <section class="p-6 md:p-8">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-3">
                <h1 class="text-2xl font-semibold text-slate-900">Customers</h1>
                <p class="text-slate-500">Manage customer accounts and roles.</p>
                <p class="text-sm text-slate-400">Backend screen scaffolded with reusable sidebar/header and AJAX navigation enabled.</p>
            </div>
        </section>
    </main>
</body>
</html>