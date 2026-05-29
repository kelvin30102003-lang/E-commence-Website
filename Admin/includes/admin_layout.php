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
        ['key' => 'payment_slips', 'label' => 'Payment Slips', 'icon' => 'receipt_long', 'href' => 'managePaymentSlips.php'],
        ['key' => 'users', 'label' => 'Users', 'icon' => 'group', 'href' => 'manageUsers.php'],
        ['key' => 'messages', 'label' => 'Messages', 'icon' => 'mail', 'href' => 'manageMessages.php'],
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
        $path = __DIR__ . '/../../' . ltrim(str_replace('../', '', $href), '/');
        $version = is_file($path) ? (string)filemtime($path) : '1';

        return $href . '?v=' . rawurlencode($version);
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

    $path = $assetsDir . '/' . $latest;
    $version = is_file($path) ? (string)filemtime($path) : '1';

    return '../backend/public/build/assets/' . $latest . '?v=' . rawurlencode($version);
}

function admin_css_file_path(): ?string
{
    static $path = null;
    static $loaded = false;

    if ($loaded) {
        return $path;
    }
    $loaded = true;

    $manifestPath = __DIR__ . '/../../backend/public/build/manifest.json';
    if (is_file($manifestPath) && is_readable($manifestPath)) {
        $raw = @file_get_contents($manifestPath);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        $entry = is_array($decoded) ? ($decoded['resources/css/admin.css'] ?? null) : null;
        $file = is_array($entry) ? (string)($entry['file'] ?? '') : '';
        if ($file !== '') {
            $candidate = __DIR__ . '/../../backend/public/build/' . ltrim($file, '/');
            if (is_file($candidate) && is_readable($candidate)) {
                $path = $candidate;
                return $path;
            }
        }
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

    $candidate = $matches[0];
    if (is_file($candidate) && is_readable($candidate)) {
        $path = $candidate;
    }

    return $path;
}

function admin_render_compiled_css_inline(): void
{
    $cssPath = admin_css_file_path();
    if ($cssPath === null) {
        return;
    }

    $css = @file_get_contents($cssPath);
    if (!is_string($css) || trim($css) === '') {
        return;
    }

    echo '<style id="admin-compiled-css">';
    echo str_replace('</style', '<\/style', $css);
    echo '</style>';
}

function admin_material_symbols_href(): string
{
    $assetPath = __DIR__ . '/../../Assect/css/material-symbols-outlined.css';
    $version = is_file($assetPath) ? (string)filemtime($assetPath) : '1';

    return '../Assect/css/material-symbols-outlined.css?v=' . $version;
}

function admin_render_critical_css(): void
{
    $tailwindPath = __DIR__ . '/../../Assect/js/tailwindcss-local.js';
    $tailwindVersion = is_file($tailwindPath) ? (string)filemtime($tailwindPath) : '1';

    echo '<script>';
    echo 'tailwind={config:{darkMode:"class",theme:{extend:{colors:{';
    echo '"secondary-fixed":"#b2f2bb","background":"#fbf9f8","on-secondary-container":"#357044","on-secondary-fixed-variant":"#145129","secondary":"#2f6a3f","inverse-on-surface":"#f2f0f0","outline":"#817476","on-tertiary":"#ffffff","on-surface":"#1b1c1c","surface-tint":"#78555e","on-primary-fixed":"#2d141c","error":"#ba1a1a","tertiary-container":"#e9ddab","surface-dim":"#dbd9d9","on-primary-fixed-variant":"#5e3e47","secondary-container":"#b2f2bb","on-tertiary-fixed":"#211c00","surface-container-lowest":"#ffffff","on-secondary":"#ffffff","on-error":"#ffffff","tertiary-fixed":"#efe3b0","surface-container-highest":"#e4e2e2","on-primary-container":"#7a5761","primary-fixed":"#ffd9e2","surface-container-low":"#f5f3f3","surface-bright":"#fbf9f8","tertiary-fixed-dim":"#d2c796","tertiary":"#665f36","on-primary":"#ffffff","on-tertiary-fixed-variant":"#4e4721","error-container":"#ffdad6","surface-variant":"#e4e2e2","inverse-surface":"#303030","on-tertiary-container":"#696139","on-surface-variant":"#4f4446","inverse-primary":"#e7bbc6","primary-container":"#ffd1dc","surface":"#fbf9f8","outline-variant":"#d3c3c5","on-error-container":"#93000a","surface-container":"#efeded","secondary-fixed-dim":"#96d5a0","primary-fixed-dim":"#e7bbc6","on-secondary-fixed":"#00210b","surface-container-high":"#eae8e7","primary":"#78555e","on-background":"#1b1c1c"';
    echo '},borderRadius:{DEFAULT:"1rem",lg:"2rem",xl:"3rem",full:"9999px"},spacing:{unit:"4px","margin-mobile":"20px","margin-desktop":"80px",xs:"4px",sm:"8px",md:"16px",lg:"24px",gutter:"16px",xl:"48px"},fontFamily:{"headline-md":["Quicksand"],"headline-lg":["Quicksand"],"body-lg":["Plus Jakarta Sans"],"body-md":["Plus Jakarta Sans"],"headline-lg-mobile":["Quicksand"],"display-lg":["Quicksand"],"label-sm":["Plus Jakarta Sans"],"label-md":["Plus Jakarta Sans"]},fontSize:{"headline-md":["24px",{lineHeight:"32px",fontWeight:"600"}],"headline-lg":["32px",{lineHeight:"40px",fontWeight:"700"}],"body-lg":["18px",{lineHeight:"28px",fontWeight:"400"}],"body-md":["16px",{lineHeight:"24px",fontWeight:"400"}],"headline-lg-mobile":["24px",{lineHeight:"32px",fontWeight:"700"}],"display-lg":["48px",{lineHeight:"56px",letterSpacing:"0",fontWeight:"700"}],"label-sm":["12px",{lineHeight:"16px",fontWeight:"700"}],"label-md":["14px",{lineHeight:"20px",letterSpacing:"0",fontWeight:"600"}]}}}}};';
    echo '</script>';
    echo '<script src="../Assect/js/tailwindcss-local.js?v=' . admin_html($tailwindVersion) . '"></script>';
    admin_render_compiled_css_inline();
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
    $adminName = trim((string)($admin['name'] ?? 'Admin'));
    $adminEmail = trim((string)($admin['email'] ?? ''));
    $initial = strtoupper(substr($adminName !== '' ? $adminName : $adminEmail, 0, 1));
    if ($initial === '') {
        $initial = 'A';
    }

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

    echo '<div class="relative flex items-center gap-3 ml-6" data-admin-header-actions>';
    echo '<div class="relative">';
    echo '<button class="relative h-10 w-10 rounded-full border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-[#78555e] transition-colors flex items-center justify-center" type="button" data-admin-notification-trigger aria-label="Notifications" aria-expanded="false">';
    echo '<span class="material-symbols-outlined">notifications</span>';
    echo '<span class="hidden absolute -right-1 -top-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] leading-[18px] text-center font-bold" data-admin-notification-badge>0</span>';
    echo '</button>';
    echo '<div class="hidden absolute right-0 mt-4 rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden z-50" style="width: 520px; max-width: calc(100vw - 1rem);" data-admin-notification-menu>';
    echo '<div class="flex items-center justify-between gap-4 px-6 py-5 border-b border-slate-100 bg-slate-50">';
    echo '<div><p class="text-xl font-bold text-slate-900">Notifications</p><p class="mt-1 text-sm text-slate-500" data-admin-notification-summary>Checking updates...</p></div>';
    echo '<button class="h-10 w-10 rounded-full border border-slate-200 bg-white text-slate-500 hover:text-[#78555e] hover:border-[#78555e]/40 flex items-center justify-center transition-colors" type="button" data-admin-notification-refresh aria-label="Refresh notifications"><span class="material-symbols-outlined text-[22px]">refresh</span></button>';
    echo '</div>';
    echo '<div class="overflow-y-auto divide-y divide-slate-100" style="min-height: 305px; max-height: calc(100vh - 8rem);" data-admin-notification-list>';
    echo '<div class="px-6 py-10 text-center text-slate-500"><span class="material-symbols-outlined mb-3 text-4xl text-slate-300">notifications</span><p class="text-base">Loading notifications...</p></div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '<a class="h-10 rounded-full border border-slate-200 bg-white pl-1 pr-3 flex items-center gap-2 hover:bg-slate-50 transition-colors" href="adminProfile.php" data-ajax="true" aria-label="Admin profile">';
    echo '<span class="h-8 w-8 rounded-full bg-[#ffd1dc] text-[#78555e] font-bold flex items-center justify-center">' . admin_html($initial) . '</span>';
    echo '<span class="hidden md:block text-left leading-tight"><span class="block text-sm font-semibold text-slate-700 max-w-36 truncate" data-admin-profile-name-label>' . admin_html($adminName !== '' ? $adminName : 'Admin') . '</span><span class="block text-xs text-slate-400 max-w-44 truncate" data-admin-profile-email-label>' . admin_html($adminEmail) . '</span></span>';
    echo '</a>';
    echo '</div>';
    echo '</header>';
    echo '<script>
(() => {
    const root = document.querySelector("[data-admin-header-actions]");
    if (!root || root.dataset.adminHeaderReady === "1") {
        return;
    }
    root.dataset.adminHeaderReady = "1";

    const notificationTrigger = root.querySelector("[data-admin-notification-trigger]");
    const notificationMenu = root.querySelector("[data-admin-notification-menu]");
    const notificationBadge = root.querySelector("[data-admin-notification-badge]");
    const notificationList = root.querySelector("[data-admin-notification-list]");
    const notificationSummary = root.querySelector("[data-admin-notification-summary]");
    const notificationRefresh = root.querySelector("[data-admin-notification-refresh]");

    const closeMenus = () => {
        if (notificationMenu) {
            notificationMenu.classList.add("hidden");
            notificationTrigger && notificationTrigger.setAttribute("aria-expanded", "false");
        }
    };

    const toggleMenu = (menu, trigger) => {
        if (!menu || !trigger) {
            return;
        }
        const willOpen = menu.classList.contains("hidden");
        menu.classList.toggle("hidden", !willOpen);
        trigger.setAttribute("aria-expanded", willOpen ? "true" : "false");
    };

    const escapeHtml = (value) => String(value || "").replace(/[&<>"\x27]/g, (char) => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        "\"": "&quot;",
        "\x27": "&#039;"
    }[char]));

    const renderNotifications = (payload) => {
        const total = Math.max(0, Number(payload.total || 0));
        if (notificationBadge) {
            notificationBadge.textContent = total > 99 ? "99+" : String(total);
            notificationBadge.classList.toggle("hidden", total <= 0);
        }
        if (notificationSummary) {
            notificationSummary.textContent = total > 0 ? `${total} item${total === 1 ? "" : "s"} need attention` : "";
        }
        if (!notificationList) {
            return;
        }
        const items = Array.isArray(payload.items) ? payload.items : [];
        if (items.length === 0) {
            notificationList.innerHTML = "<div class=\"px-8 py-20 text-center text-slate-500\"><p class=\"text-sm font-medium text-slate-500 lowercase\">no new message</p></div>";
            return;
        }
        notificationList.innerHTML = items.map((item) => {
            const icon = escapeHtml(item.icon || "notifications");
            const href = escapeHtml(item.href || "#");
            const title = escapeHtml(item.title || "Notification");
            const detail = escapeHtml(item.detail || "");
            const time = escapeHtml(item.time || "");
            return `<a class=\"flex gap-4 px-6 py-5 hover:bg-slate-50 transition-colors\" href=\"${href}\"><span class=\"h-12 w-12 shrink-0 rounded-full bg-[#ffd1dc] text-[#78555e] flex items-center justify-center\"><span class=\"material-symbols-outlined text-[24px]\">${icon}</span></span><span class=\"min-w-0 flex-1\"><span class=\"block text-base font-semibold text-slate-900 truncate\">${title}</span><span class=\"mt-1 block text-sm leading-6 text-slate-600 line-clamp-2\">${detail}</span><span class=\"mt-2 block text-xs font-medium text-slate-400\">${time}</span></span><span class=\"material-symbols-outlined self-center text-slate-300\">chevron_right</span></a>`;
        }).join("");
    };

    const loadNotifications = async () => {
        try {
            const response = await fetch("adminNotifications.php", {
                credentials: "same-origin",
                headers: { "Accept": "application/json", "X-Requested-With": "XMLHttpRequest" }
            });
            const payload = await response.json();
            if (!response.ok || !payload.ok) {
                throw new Error(payload.message || "Unable to load notifications.");
            }
            renderNotifications(payload);
        } catch (error) {
            if (notificationSummary) {
                notificationSummary.textContent = "Notification check failed";
            }
        }
    };

    notificationTrigger && notificationTrigger.addEventListener("click", () => {
        toggleMenu(notificationMenu, notificationTrigger);
        loadNotifications();
    });
    notificationRefresh && notificationRefresh.addEventListener("click", loadNotifications);
    document.addEventListener("click", (event) => {
        if (!root.contains(event.target)) {
            closeMenus();
        }
    });
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeMenus();
        }
    });
    loadNotifications();
    window.setInterval(() => {
        if (!document.hidden) {
            loadNotifications();
        }
    }, 8000);
})();
</script>';
}
