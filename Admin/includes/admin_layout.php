<?php

declare(strict_types=1);

function admin_nav_items(): array
{
    return [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'href' => 'adminDashboard.php'],
        ['key' => 'products', 'label' => 'Inventory', 'icon' => 'inventory_2', 'href' => 'manageProducts.php'],
        ['key' => 'categories', 'label' => 'Categories', 'icon' => 'widgets', 'href' => 'manageCategories.php'],
        ['key' => 'brands', 'label' => 'Brands', 'icon' => 'bookmark', 'href' => 'manageBrands.php'],
        ['key' => 'orders', 'label' => 'Orders', 'icon' => 'shopping_cart', 'href' => 'manageOrders.php'],
        ['key' => 'shipping', 'label' => 'Shipping', 'icon' => 'local_shipping', 'href' => 'manageShipping.php'],
        ['key' => 'coupons', 'label' => 'Coupons', 'icon' => 'sell', 'href' => 'manageCoupons.php'],
        ['key' => 'payments', 'label' => 'Payments', 'icon' => 'payments', 'href' => 'managePayments.php'],
        ['key' => 'users', 'label' => 'Users', 'icon' => 'group', 'href' => 'manageUsers.php'],
        ['key' => 'logs', 'label' => 'Analytics', 'icon' => 'analytics', 'href' => 'activityLogs.php'],
    ];
}

function admin_render_sidebar(array $admin, string $activeKey = 'dashboard', array $options = []): void
{
    $storeHref = (string)($options['store_href'] ?? '../Users/Home.php');
    $logoutHref = (string)($options['logout_href'] ?? 'adminLogout.php');

    echo '<aside class="flex flex-col h-screen w-64 fixed left-0 top-0 bg-white shadow-md z-50 py-6" data-admin-sidebar="true">';
    echo '<div class="px-6 mb-10">';
    echo '<h1 class="font-semibold text-xl text-[#78555e]">LuvShop Admin</h1>';
    echo '<p class="text-xs text-slate-500">Management Portal</p>';
    echo '</div>';

    echo '<nav class="flex-1 space-y-1">';
    foreach (admin_nav_items() as $item) {
        $isActive = $item['key'] === $activeKey;
        $class = $isActive
            ? 'bg-[#ffd1dc] text-[#7a5761] font-semibold'
            : 'text-slate-600 hover:bg-slate-100';
        echo '<a class="' . $class . ' rounded-lg mx-2 px-4 py-3 flex items-center gap-3" data-ajax="true" href="' . admin_html((string)$item['href']) . '">';
        echo '<span class="material-symbols-outlined">' . admin_html((string)$item['icon']) . '</span>';
        echo '<span>' . admin_html((string)$item['label']) . '</span>';
        echo '</a>';
    }
    echo '</nav>';

    echo '<div class="px-2 mt-auto">';
    echo '<a class="w-full bg-[#b2f2bb] text-[#145129] font-semibold py-3 rounded-lg mb-3 flex items-center justify-center gap-2 hover:opacity-90 transition-opacity" href="' . admin_html($storeHref) . '">';
    echo '<span class="material-symbols-outlined text-sm">visibility</span>View Live Store</a>';
    echo '<a class="text-slate-600 hover:bg-slate-100 rounded-lg px-4 py-3 flex items-center gap-3" href="' . admin_html($logoutHref) . '">';
    echo '<span class="material-symbols-outlined">logout</span><span>Logout</span></a>';
    echo '</div>';
    echo '</aside>';

    $ajaxFilePath = __DIR__ . '/../../Assect/js/site-ajax.js';
    $ajaxVersion = is_file($ajaxFilePath) ? (string)filemtime($ajaxFilePath) : '1';
    echo '<script defer src="../Assect/js/site-ajax.js?v=' . admin_html($ajaxVersion) . '"></script>';
}

function admin_vite_asset_href(string $input): ?string
{
    static $manifest = null;
    static $loaded = false;

    if (!$loaded) {
        $loaded = true;
        $manifestPath = __DIR__ . '/../../backend/public/build/manifest.json';
        if (is_file($manifestPath) && is_readable($manifestPath)) {
            $raw = @file_get_contents($manifestPath);
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $manifest = $decoded;
                }
            }
        }
    }

    if (!is_array($manifest)) {
        return null;
    }

    $entry = $manifest[$input] ?? null;
    if (!is_array($entry)) {
        return null;
    }

    $file = $entry['file'] ?? null;
    if (!is_string($file) || trim($file) === '') {
        return null;
    }

    return '../backend/public/build/' . ltrim($file, '/');
}

function admin_css_href(): ?string
{
    $href = admin_vite_asset_href('resources/css/admin.css');
    if ($href !== null) {
        return $href;
    }

    $assetsDir = __DIR__ . '/../../backend/public/build/assets';
    if (!is_dir($assetsDir)) {
        return null;
    }

    $matches = glob($assetsDir . '/admin-*.css');
    if (!is_array($matches) || count($matches) === 0) {
        return null;
    }

    usort($matches, static function (string $a, string $b): int {
        return filemtime($b) <=> filemtime($a);
    });

    $latest = basename($matches[0]);
    if ($latest === '') {
        return null;
    }

    return '../backend/public/build/assets/' . $latest;
}

function admin_material_symbols_href(): string
{
    $assetPath = __DIR__ . '/../../Assect/css/material-symbols-outlined.css';
    $version = is_file($assetPath) ? (string)filemtime($assetPath) : '1';

    return '../Assect/css/material-symbols-outlined.css?v=' . $version;
}

function admin_render_critical_css(): void
{
    echo '<style id="admin-critical-css">';
    echo 'html{background:#fbf9f8;}';
    echo 'body{margin:0;min-height:100vh;background:#fbf9f8;color:#1b1c1c;font-family:"Plus Jakarta Sans","Segoe UI",sans-serif;-webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility;}';
    echo '[data-admin-sidebar="true"]{position:fixed;left:0;top:0;z-index:50;display:flex;width:16rem;height:100vh;flex-direction:column;background:#fff;padding-block:1.5rem;box-shadow:0 4px 18px rgba(15,23,42,.08);}';
    echo '#app-main{min-height:100vh;margin-left:16rem;background:#fbf9f8;}';
    echo '#app-main>header{height:4rem;}';
    echo '.material-symbols-outlined{display:inline-flex;width:1em;min-width:1em;height:1em;overflow:hidden;line-height:1;vertical-align:middle;align-items:center;justify-content:center;letter-spacing:0;white-space:nowrap;}';
    echo ':where(input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=file]),select,textarea){box-sizing:border-box;display:block;min-height:2.5rem;width:100%;border:1px solid #d3c3c5;border-radius:.75rem;background:#fff;color:#1b1c1c;padding:.625rem .875rem;font:inherit;line-height:1.25rem;}';
    echo ':where(input[type=file]){box-sizing:border-box;display:block;width:100%;border:1px solid #d3c3c5;border-radius:.75rem;background:#fff;color:#1b1c1c;padding:.5rem .875rem;font:inherit;line-height:1.5rem;}';
    echo ':where(input[type=checkbox],input[type=radio]){box-sizing:border-box;width:1rem;height:1rem;border:1px solid #d3c3c5;background:#fff;vertical-align:middle;}';
    echo ':where(input:focus,select:focus,textarea:focus){outline:0;border-color:#78555e;box-shadow:0 0 0 3px rgba(255,209,220,.85);}';
    echo '.max-w-md{max-width:28rem!important;}@media(min-width:48rem){.md\\:max-w-md{max-width:28rem!important;}}';
    echo '.admin-ajax-loading #app-main{opacity:.72;}';
    echo '@media(max-width:767px){[data-admin-sidebar="true"]{position:relative;width:100%;height:auto;}#app-main{margin-left:0;}}';
    echo '</style>';
}

function admin_render_header(array $admin, array $options = []): void
{
    $searchEnabled = !isset($options['search_enabled']) || (bool)$options['search_enabled'] === true;
    $searchAction = (string)($options['search_action'] ?? '');
    $searchMethod = strtolower((string)($options['search_method'] ?? 'get')) === 'post' ? 'post' : 'get';
    $searchName = (string)($options['search_name'] ?? 'q');
    $searchValue = (string)($options['search_value'] ?? '');
    $searchPlaceholder = (string)($options['search_placeholder'] ?? 'Search...');
    $searchHidden = isset($options['search_hidden']) && is_array($options['search_hidden']) ? $options['search_hidden'] : [];

    echo '<header class="h-16 sticky top-0 bg-white shadow-sm flex items-center justify-between px-6 z-40">';

    if ($searchEnabled) {
        echo '<form method="' . admin_html($searchMethod) . '" class="w-full max-w-md" data-ajax="true"';
        if ($searchAction !== '') {
            echo ' action="' . admin_html($searchAction) . '"';
        }
        echo '>';
        echo '<div class="relative">';
        echo '<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>';
        echo '<input class="w-full h-10 pl-10 pr-4 bg-slate-100 border-none rounded-full focus:ring-2 focus:ring-[#ffd1dc] text-sm"';
        echo ' name="' . admin_html($searchName) . '"';
        echo ' placeholder="' . admin_html($searchPlaceholder) . '"';
        echo ' type="text" value="' . admin_html($searchValue) . '" />';
        echo '</div>';

        foreach ($searchHidden as $hiddenName => $hiddenValue) {
            if ($hiddenName === $searchName) {
                continue;
            }
            echo '<input type="hidden" name="' . admin_html((string)$hiddenName) . '" value="' . admin_html((string)$hiddenValue) . '" />';
        }
        echo '</form>';
    } else {
        echo '<div class="w-full"></div>';
    }

    echo '<div class="flex items-center gap-3 ml-6">';
    echo '<span class="text-sm text-slate-600 hidden md:block">' . admin_html((string)($admin['name'] ?? 'Admin')) . '</span>';
    echo '<span class="text-xs text-slate-400 hidden md:block">' . admin_html((string)($admin['email'] ?? '')) . '</span>';
    echo '</div>';
    echo '</header>';
}
