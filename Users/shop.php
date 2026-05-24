<?php

declare(strict_types=1);

$activePage = 'shop';

require_once __DIR__ . '/includes/shop_backend.php';
shop_start_session();

$cartPopup = shop_cart_pull_flash();

$filters = shop_normalize_filters($_GET);

$dbError = null;
$categories = [];
$products = [];
$totalRows = 0;
$totalPages = 1;
$currentPage = 1;
$pagePayloadCacheKey = '';
$pagePayloadCacheHit = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = trim((string)($_POST['action'] ?? ''));
    if ($postAction === 'add_to_cart') {
        $addProductId = max(0, (int)($_POST['product_id'] ?? 0));
        $addVariantId = max(0, (int)($_POST['variant_id'] ?? 0));
        $addQuantity = max(1, min(99, (int)($_POST['quantity'] ?? 1)));

        try {
            $pdo = shop_db();
            $added = shop_cart_add_item($pdo, $addProductId, $addQuantity, $addVariantId);
            shop_cart_set_flash($added);

            $queryString = trim((string)($_SERVER['QUERY_STRING'] ?? ''));
            $redirect = 'shop.php' . ($queryString !== '' ? '?' . $queryString : '');
            header('Location: ' . $redirect);
            exit;
        } catch (Throwable $exception) {
            $dbError = $exception->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pagePayloadCacheKey = shop_cache_key('shop_page_payload', [
        'q' => (string)$filters['q'],
        'category' => (string)$filters['category'],
        'sort' => (string)$filters['sort'],
        'page' => (int)$filters['page'],
        'per_page' => (int)$filters['per_page'],
        'v' => 1,
    ]);

    $cachedPayload = null;
    if (shop_cache_fetch($pagePayloadCacheKey, $cachedPayload) && is_array($cachedPayload)) {
        $cachedCategories = $cachedPayload['categories'] ?? null;
        $cachedProducts = $cachedPayload['products'] ?? null;
        $cachedFilters = $cachedPayload['filters'] ?? null;
        if (is_array($cachedCategories) && is_array($cachedProducts) && is_array($cachedFilters)) {
            $filters = shop_normalize_filters($cachedFilters);
            $categories = $cachedCategories;
            $products = $cachedProducts;
            $totalRows = max(0, (int)($cachedPayload['total_rows'] ?? 0));
            $totalPages = max(1, (int)($cachedPayload['total_pages'] ?? 1));
            $currentPage = max(1, min((int)($cachedPayload['current_page'] ?? 1), $totalPages));
            $pagePayloadCacheHit = true;
        }
    }
}

if (!$pagePayloadCacheHit) {
    try {
        $pdo = shop_db();
        $categories = shop_fetch_categories($pdo);

        $listData = shop_fetch_products($pdo, $filters);
        $products = $listData['rows'];
        $totalRows = (int)$listData['total'];
        $totalPages = max(1, (int)ceil($totalRows / $filters['per_page']));
        $currentPage = min($filters['page'], $totalPages);

        if ($currentPage !== $filters['page']) {
            $filters['page'] = $currentPage;
            $listData = shop_fetch_products($pdo, $filters);
            $products = $listData['rows'];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $pagePayloadCacheKey !== '') {
            shop_cache_store($pagePayloadCacheKey, [
                'filters' => $filters,
                'categories' => $categories,
                'products' => $products,
                'total_rows' => $totalRows,
                'total_pages' => $totalPages,
                'current_page' => $currentPage,
            ], 600);
        }
    } catch (Throwable $exception) {
        $dbError = $exception->getMessage();
    }
}

$startRow = $totalRows === 0 ? 0 : (($currentPage - 1) * $filters['per_page']) + 1;
$endRow = $totalRows === 0 ? 0 : min($currentPage * $filters['per_page'], $totalRows);
$isAjaxRequest = shop_is_ajax_request();

?>
<?php if (!$isAjaxRequest): ?>
<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&amp;family=Plus+Jakarta+Sans:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .soft-shadow {
            box-shadow: 0 10px 30px -5px rgba(120, 85, 94, 0.08);
        }
        .inner-glow {
            box-shadow: inset 0 2px 4px 0 rgba(255, 255, 255, 0.4);
        }
    </style>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "secondary-fixed": "#b2f2bb",
                        "background": "#fbf9f8",
                        "on-secondary-container": "#357044",
                        "on-secondary-fixed-variant": "#145129",
                        "secondary": "#2f6a3f",
                        "inverse-on-surface": "#f2f0f0",
                        "outline": "#817476",
                        "on-tertiary": "#ffffff",
                        "on-surface": "#1b1c1c",
                        "surface-tint": "#78555e",
                        "on-primary-fixed": "#2d141c",
                        "error": "#ba1a1a",
                        "tertiary-container": "#e9ddab",
                        "surface-dim": "#dbd9d9",
                        "on-primary-fixed-variant": "#5e3e47",
                        "secondary-container": "#b2f2bb",
                        "on-tertiary-fixed": "#211c00",
                        "surface-container-lowest": "#ffffff",
                        "on-secondary": "#ffffff",
                        "on-error": "#ffffff",
                        "tertiary-fixed": "#efe3b0",
                        "surface-container-highest": "#e4e2e2",
                        "on-primary-container": "#7a5761",
                        "primary-fixed": "#ffd9e2",
                        "surface-container-low": "#f5f3f3",
                        "surface-bright": "#fbf9f8",
                        "tertiary-fixed-dim": "#d2c796",
                        "tertiary": "#665f36",
                        "on-primary": "#ffffff",
                        "on-tertiary-fixed-variant": "#4e4721",
                        "error-container": "#ffdad6",
                        "surface-variant": "#e4e2e2",
                        "inverse-surface": "#303030",
                        "on-tertiary-container": "#696139",
                        "on-surface-variant": "#4f4446",
                        "inverse-primary": "#e7bbc6",
                        "primary-container": "#ffd1dc",
                        "surface": "#fbf9f8",
                        "outline-variant": "#d3c3c5",
                        "on-error-container": "#93000a",
                        "surface-container": "#efeded",
                        "secondary-fixed-dim": "#96d5a0",
                        "primary-fixed-dim": "#e7bbc6",
                        "on-secondary-fixed": "#00210b",
                        "surface-container-high": "#eae8e7",
                        "primary": "#78555e",
                        "on-background": "#1b1c1c"
                    },
                    "borderRadius": {
                        "DEFAULT": "1rem",
                        "lg": "2rem",
                        "xl": "3rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "unit": "4px",
                        "margin-mobile": "20px",
                        "margin-desktop": "80px",
                        "xs": "4px",
                        "lg": "24px",
                        "gutter": "16px",
                        "xl": "48px",
                        "sm": "8px",
                        "md": "16px"
                    },
                    "fontFamily": {
                        "headline-md": ["Quicksand"],
                        "headline-lg": ["Quicksand"],
                        "body-lg": ["Plus Jakarta Sans"],
                        "body-md": ["Plus Jakarta Sans"],
                        "headline-lg-mobile": ["Quicksand"],
                        "display-lg": ["Quicksand"],
                        "label-sm": ["Plus Jakarta Sans"],
                        "label-md": ["Plus Jakarta Sans"]
                    },
                    "fontSize": {
                        "headline-md": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                        "headline-lg": ["32px", {"lineHeight": "40px", "fontWeight": "700"}],
                        "body-lg": ["18px", {"lineHeight": "28px", "fontWeight": "400"}],
                        "body-md": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                        "headline-lg-mobile": ["24px", {"lineHeight": "32px", "fontWeight": "700"}],
                        "display-lg": ["48px", {"lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                        "label-sm": ["12px", {"lineHeight": "16px", "fontWeight": "700"}],
                        "label-md": ["14px", {"lineHeight": "20px", "letterSpacing": "0.01em", "fontWeight": "600"}]
                    }
                },
            },
        }
    </script>
</head>
<body class="bg-background text-on-surface font-body-md overflow-x-hidden">
<?php require_once __DIR__ . '/../Templates/header.php'; ?>
<main class="pt-24 pb-xl px-4 md:px-margin-desktop max-w-[1280px] mx-auto" id="app-main">
<?php endif; ?>
<section data-shop-ajax-root>
    <?php if ($dbError !== null): ?>
        <section class="mb-lg rounded-xl border border-red-200 bg-error-container px-md py-sm text-sm text-on-error-container">
            DB connection error: <?= shop_h($dbError) ?>
        </section>
    <?php endif; ?>
    <?php if (is_array($cartPopup)): ?>
        <?php
            $popupName = trim((string)($cartPopup['product_name'] ?? 'Product'));
            $popupVariant = trim((string)($cartPopup['variant_name'] ?? ''));
            $popupImage = trim((string)($cartPopup['image'] ?? ''));
            $popupQty = max(1, (int)($cartPopup['quantity'] ?? 1));
            $popupPrice = (float)($cartPopup['unit_price'] ?? 0);
            $popupLineTotal = round($popupQty * $popupPrice, 2);
        ?>
        <section class="mb-lg fixed top-24 right-4 z-[70] w-[min(520px,calc(100%-2rem))] bg-white border border-outline-variant rounded-xl shadow-xl overflow-hidden" data-cart-popup>
            <div class="px-lg py-md border-b border-outline-variant/40 flex items-center justify-between">
                <h3 class="text-xl font-semibold text-slate-700">Item(s) added to your cart</h3>
                <button aria-label="Close cart popup" class="text-on-surface-variant hover:text-primary" data-close-cart-popup type="button">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="px-lg py-md flex items-center gap-md">
                <div class="w-20 h-20 rounded-lg bg-surface-container-low overflow-hidden flex-shrink-0">
                    <?php if ($popupImage !== ''): ?>
                        <img alt="<?= shop_h($popupName) ?>" class="w-full h-full object-cover" src="<?= shop_h($popupImage) ?>"/>
                    <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-br from-rose-100 to-pink-200 flex items-center justify-center">
                            <span class="text-primary font-bold"><?= shop_h(strtoupper(substr($popupName, 0, 1))) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-lg font-semibold text-on-surface leading-snug break-words">
                        <?= $popupQty ?> x <?= shop_h($popupName) ?>
                    </p>
                    <?php if ($popupVariant !== ''): ?>
                        <p class="text-sm text-on-surface-variant"><?= shop_h($popupVariant) ?></p>
                    <?php endif; ?>
                </div>
                <div class="text-lg font-semibold text-on-surface whitespace-nowrap">
                    <?= shop_h(shop_money($popupLineTotal)) ?>
                </div>
            </div>
            <div class="px-lg pb-lg">
                <a class="w-full inline-flex justify-center items-center rounded-xl bg-primary text-on-primary py-md font-semibold text-2xl leading-none hover:opacity-90" href="cart.php">
                    View cart
                </a>
            </div>
        </section>
    <?php endif; ?>

<!-- Search and Filter Section -->
<section class="mb-xl flex flex-col md:flex-row md:items-center justify-between gap-md">
<form class="relative w-full md:max-w-md group" data-shop-filter-form method="get">
<input class="w-full h-14 pl-12 pr-14 rounded-full bg-surface-container-low border-none focus:ring-2 focus:ring-primary transition-all duration-300 text-body-md placeholder-outline shadow-sm group-hover:shadow-md" name="q" placeholder="Search for cuteness..." type="text" value="<?= shop_h($filters['q']) ?>"/>
<input name="category" type="hidden" value="<?= shop_h($filters['category']) ?>"/>
<input name="sort" type="hidden" value="<?= shop_h($filters['sort']) ?>"/>
<input name="per_page" type="hidden" value="<?= (int)$filters['per_page'] ?>"/>
<input name="page" type="hidden" value="1"/>
<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline" data-icon="search">search</span>
<button class="absolute right-2 top-1/2 -translate-y-1/2 h-10 px-4 rounded-full bg-primary text-on-primary text-label-md font-label-md" type="submit">Go</button>
</form>
<div class="flex items-center gap-sm overflow-x-auto pb-2 scrollbar-hide no-scrollbar">
    <?php $allQuery = shop_build_query($filters, ['category' => '', 'page' => 1]); ?>
<a class="px-6 py-2 rounded-full text-label-md font-label-md whitespace-nowrap active:scale-95 transition-transform <?= $filters['category'] === '' ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant hover:bg-surface-variant transition-colors' ?>" href="shop.php<?= shop_h($allQuery) ?>">All</a>
    <?php foreach ($categories as $category): ?>
        <?php
            $slug = trim((string)($category['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }
            $name = trim((string)($category['name'] ?? 'Category'));
            $query = shop_build_query($filters, ['category' => $slug, 'page' => 1]);
            $isActiveCategory = $filters['category'] === $slug;
        ?>
<a class="px-6 py-2 rounded-full text-label-md font-label-md whitespace-nowrap active:scale-95 transition-transform <?= $isActiveCategory ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant hover:bg-surface-variant transition-colors' ?>" href="shop.php<?= shop_h($query) ?>"><?= shop_h($name) ?></a>
    <?php endforeach; ?>
<a class="p-2 rounded-full bg-surface-container-high text-on-surface-variant flex items-center justify-center hover:bg-surface-variant transition-colors" href="shop.php">
<span class="material-symbols-outlined" data-icon="tune">tune</span>
</a>
</div>
</section>

    <?php if ($totalRows > 0): ?>
        <section class="mb-md text-sm text-on-surface-variant">
            Showing <?= number_format($startRow) ?> to <?= number_format($endRow) ?> of <?= number_format($totalRows) ?> products
        </section>
    <?php endif; ?>

<!-- Product Grid -->
<section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-lg">
    <?php if (count($products) === 0): ?>
        <div class="col-span-full bg-surface-container-lowest rounded-lg p-xl soft-shadow text-center">
            <h3 class="text-headline-md font-headline-md text-primary mb-xs">No products found</h3>
            <p class="text-body-md text-on-surface-variant">Try another keyword or category.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($products as $index => $product): ?>
        <?php
            $name = trim((string)($product['name'] ?? 'Unnamed Product'));
            $image = trim((string)($product['image'] ?? ''));
            $images = [];
            if (isset($product['images']) && is_array($product['images'])) {
                foreach ($product['images'] as $galleryImage) {
                    $galleryImage = trim((string)$galleryImage);
                    if ($galleryImage !== '') {
                        $images[] = $galleryImage;
                    }
                }
            }
            if (count($images) === 0 && $image !== '') {
                $images[] = $image;
            }
            $primaryImage = count($images) > 0 ? $images[0] : '';
            $galleryJson = '';
            if (count($images) > 1) {
                $encodedGallery = json_encode(array_values(array_unique($images)), JSON_UNESCAPED_SLASHES);
                if (is_string($encodedGallery)) {
                    $galleryJson = $encodedGallery;
                }
            }
            $categoryName = trim((string)($product['category_name'] ?? ''));
            $price = (float)($product['price'] ?? 0);
            $comparePriceRaw = $product['compare_price'] ?? null;
            $comparePrice = $comparePriceRaw !== null ? (float)$comparePriceRaw : null;
            $stock = (int)($product['stock_quantity'] ?? 0);
            $productDetailLink = shop_product_detail_url($product);

            $badgeLabel = 'New In';
            $badgeClass = 'bg-tertiary-container text-on-tertiary-container';
            if ($stock <= 0) {
                $badgeLabel = 'Out of Stock';
                $badgeClass = 'bg-error-container text-on-error-container';
            } elseif ($comparePrice !== null && $comparePrice > $price) {
                $badgeLabel = 'Sale';
                $badgeClass = 'bg-error-container text-on-error-container';
            } elseif ($categoryName !== '') {
                $badgeLabel = $categoryName;
                $badgeClass = 'bg-secondary-container text-on-secondary-container';
            } elseif ($index % 3 === 1) {
                $badgeLabel = 'Limited';
            } elseif ($index % 3 === 2) {
                $badgeLabel = 'Bestseller';
                $badgeClass = 'bg-secondary-container text-on-secondary-container';
            }
        ?>
        <?php $galleryAttribute = $galleryJson !== '' ? ' data-product-gallery="' . shop_h($galleryJson) . '"' : ''; ?>
<div class="group bg-surface-container-lowest rounded-[28px] p-lg soft-shadow hover:-translate-y-2 transition-all duration-500 flex flex-col h-full">
<a class="relative aspect-[4/5] rounded-2xl overflow-hidden mb-md bg-surface-container-low block"<?= $galleryAttribute ?> href="<?= shop_h($productDetailLink) ?>">
    <?php if ($primaryImage !== ''): ?>
<img alt="<?= shop_h($name) ?>" class="w-full h-full object-cover transition-transform duration-700 transition-opacity duration-200 group-hover:scale-105" data-gallery-image="1" decoding="async" loading="lazy" src="<?= shop_h($primaryImage) ?>"/>
    <?php else: ?>
<div class="w-full h-full bg-gradient-to-br <?= shop_h(shop_placeholder_gradient($index)) ?> flex items-center justify-center">
<span class="text-2xl font-bold text-primary"><?= shop_h(strtoupper(substr($name, 0, 1))) ?></span>
</div>
    <?php endif; ?>
<span class="absolute top-4 left-4 <?= shop_h($badgeClass) ?> text-label-sm px-3 py-1 rounded-full font-label-sm"><?= shop_h($badgeLabel) ?></span>
<button class="absolute top-4 right-4 w-10 h-10 bg-white/80 backdrop-blur-md rounded-full flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all shadow-sm" type="button">
<span class="material-symbols-outlined" data-icon="favorite">favorite</span>
</button>
</a>
<div class="flex-grow min-w-0">
<h3 class="text-lg sm:text-xl font-semibold text-primary mb-xs leading-snug">
    <a class="hover:opacity-80 transition-opacity [display:-webkit-box] [-webkit-box-orient:vertical] [-webkit-line-clamp:2] overflow-hidden break-words" href="<?= shop_h($productDetailLink) ?>"><?= shop_h($name) ?></a>
</h3>
<div class="flex items-end justify-between mt-auto gap-sm flex-wrap pt-sm">
<div class="flex flex-col min-w-0">
<span class="text-base sm:text-lg font-semibold text-on-surface break-words leading-tight"><?= shop_h(shop_money($price)) ?></span>
    <?php if ($comparePrice !== null && $comparePrice > $price): ?>
<span class="text-xs text-on-surface-variant line-through"><?= shop_h(shop_money($comparePrice)) ?></span>
    <?php endif; ?>
</div>
<form method="post">
<input name="action" type="hidden" value="add_to_cart"/>
<input name="product_id" type="hidden" value="<?= (int)($product['id'] ?? 0) ?>"/>
<input name="variant_id" type="hidden" value="0"/>
<input name="quantity" type="hidden" value="1"/>
<button class="h-10 px-4 rounded-full text-sm font-label-md flex items-center gap-xs whitespace-nowrap transition-all inner-glow <?= $stock > 0 ? 'bg-primary text-on-primary hover:opacity-90 active:scale-95' : 'bg-surface-container-high text-on-surface-variant cursor-not-allowed' ?>" type="submit" <?= $stock > 0 ? '' : 'disabled' ?>>
<span class="material-symbols-outlined" data-icon="add_shopping_cart">add_shopping_cart</span>
                            Add
                        </button>
</form>
</div>
</div>
</div>
    <?php endforeach; ?>
</section>
<!-- Pagination -->
<nav class="mt-xl flex items-center justify-center gap-sm">
    <?php
        $prevPage = max(1, $currentPage - 1);
        $nextPage = min($totalPages, $currentPage + 1);
        $pageStart = max(1, $currentPage - 2);
        $pageEnd = min($totalPages, $currentPage + 2);
    ?>
<a class="w-10 h-10 rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high transition-colors <?= $currentPage <= 1 ? 'pointer-events-none opacity-40' : '' ?>" href="shop.php<?= shop_h(shop_build_query($filters, ['page' => $prevPage])) ?>">
<span class="material-symbols-outlined" data-icon="chevron_left">chevron_left</span>
</a>

    <?php if ($pageStart > 1): ?>
<a class="w-10 h-10 rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high transition-colors" href="shop.php<?= shop_h(shop_build_query($filters, ['page' => 1])) ?>">1</a>
        <?php if ($pageStart > 2): ?>
<span class="text-on-surface-variant px-2">...</span>
        <?php endif; ?>
    <?php endif; ?>

    <?php for ($page = $pageStart; $page <= $pageEnd; $page++): ?>
<a class="w-10 h-10 rounded-full flex items-center justify-center <?= $page === $currentPage ? 'bg-primary text-on-primary font-bold shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-high transition-colors' ?>" href="shop.php<?= shop_h(shop_build_query($filters, ['page' => $page])) ?>"><?= $page ?></a>
    <?php endfor; ?>

    <?php if ($pageEnd < $totalPages): ?>
        <?php if ($pageEnd < ($totalPages - 1)): ?>
<span class="text-on-surface-variant px-2">...</span>
        <?php endif; ?>
<a class="w-10 h-10 rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high transition-colors" href="shop.php<?= shop_h(shop_build_query($filters, ['page' => $totalPages])) ?>"><?= $totalPages ?></a>
    <?php endif; ?>

<a class="w-10 h-10 rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high transition-colors <?= $currentPage >= $totalPages ? 'pointer-events-none opacity-40' : '' ?>" href="shop.php<?= shop_h(shop_build_query($filters, ['page' => $nextPage])) ?>">
<span class="material-symbols-outlined" data-icon="chevron_right">chevron_right</span>
</a>
</nav>
</section>
<?php if (!$isAjaxRequest): ?>
</main>
<!-- NavigationDrawer (Mobile Sidebar) -->
<div class="hidden fixed inset-0 z-[60] flex pointer-events-none" id="drawer">
<div class="bg-surface h-full w-80 rounded-r-lg shadow-2xl flex flex-col p-md pointer-events-auto transition-transform -translate-x-full duration-500">
<div class="flex items-center gap-md mb-xl p-md border-b border-outline-variant/30">
<div class="w-12 h-12 rounded-full bg-primary-container overflow-hidden">
<img alt="User Profile" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAo7eTO1_fMWlHW0ZTmn4bk0a613MBTAycLnPG-nkuduOQSFYucHj-v-IRmDEA9C04bMRhgCqjAwisrpOjYW__9DBvkcmdeYtVbZVXWJw6gEyVTsWBOikoEfiFig1WhxvzDIlv2XhcY1dX4RtOLq_VwOT8W_o8LUYOrsS-SS4zabfm-pIfcz8zf39cYaG-STjXamuaE-zrg7NePRFwciWqX1tD9AZKjXjGw8ruQqbtUCwNel1h5XrT6dNdgEzETItu3JvaB8yiAaYA"/>
</div>
<div>
<h4 class="text-body-md font-bold text-on-surface">Welcome, Friend!</h4>
<p class="text-label-sm text-on-surface-variant">Start your cute journey</p>
</div>
</div>
<nav class="flex flex-col gap-sm">
<a class="flex items-center gap-md p-md bg-secondary-container text-on-secondary-container font-bold rounded-xl" href="#">
<span class="material-symbols-outlined" data-icon="auto_awesome">auto_awesome</span>
                    New Arrivals
                </a>
<a class="flex items-center gap-md p-md text-on-surface-variant hover:bg-surface-container-high rounded-xl transition-all" href="#">
<span class="material-symbols-outlined" data-icon="stars">stars</span>
                    Best Sellers
                </a>
<a class="flex items-center gap-md p-md text-on-surface-variant hover:bg-surface-container-high rounded-xl transition-all" href="#">
<span class="material-symbols-outlined" data-icon="category">category</span>
                    Categories
                </a>
<a class="flex items-center gap-md p-md text-on-surface-variant hover:bg-surface-container-high rounded-xl transition-all" href="#">
<span class="material-symbols-outlined" data-icon="sell">sell</span>
                    Sale
                </a>
</nav>
<div class="mt-auto p-md text-center">
<span class="text-label-sm text-outline-variant">Member since 2024</span>
</div>
</div>
<div class="flex-grow bg-black/20 backdrop-blur-sm pointer-events-auto"></div>
</div>
<?php require_once __DIR__ . '/../Templates/footer.php'; ?>
<!-- BottomNavBar (Mobile Only) -->
<nav class="md:hidden fixed bottom-0 left-0 w-full z-50 flex justify-around items-center px-2 pb-safe py-3 bg-surface-container-low rounded-t-lg shadow-[0_-4px_12px_rgba(120,85,94,0.08)] border-t border-outline-variant/30">
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1.5 hover:bg-surface-container-high transition-colors" data-ajax="true" href="Home.php">
<span class="material-symbols-outlined" data-icon="home">home</span>
<span class="text-label-sm font-label-sm">Home</span>
</a>
<a class="flex flex-col items-center justify-center bg-primary-container text-on-primary-container rounded-full px-4 py-1.5 transition-all duration-300" data-ajax="true" href="shop.php">
<span class="material-symbols-outlined" data-icon="storefront">storefront</span>
<span class="text-label-sm font-label-sm">Shop</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1.5 hover:bg-surface-container-high transition-colors" href="contactUs.php">
<span class="material-symbols-outlined" data-icon="chat_bubble">chat_bubble</span>
<span class="text-label-sm font-label-sm">Contact</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1.5 hover:bg-surface-container-high transition-colors" href="profile.php">
<span class="material-symbols-outlined" data-icon="person">person</span>
<span class="text-label-sm font-label-sm">Profile</span>
</a>
</nav>
<script>
(() => {
    const popup = document.querySelector('[data-cart-popup]');
    if (!popup) {
        return;
    }

    const closeButton = popup.querySelector('[data-close-cart-popup]');
    if (closeButton) {
        closeButton.addEventListener('click', () => {
            popup.remove();
        });
    }
})();

(() => {
    const initGalleryCards = (scope = document) => {
        const existingIntervals = Array.isArray(window.shopGalleryIntervals) ? window.shopGalleryIntervals : [];
        existingIntervals.forEach((intervalId) => {
            window.clearInterval(intervalId);
        });
        window.shopGalleryIntervals = [];

        const galleryCards = scope.querySelectorAll('[data-product-gallery]');
        galleryCards.forEach((card) => {
            const raw = card.getAttribute('data-product-gallery');
            if (!raw) {
                return;
            }

            let parsed;
            try {
                parsed = JSON.parse(raw);
            } catch (error) {
                return;
            }

            if (!Array.isArray(parsed)) {
                return;
            }

            const images = parsed
                .filter((value) => typeof value === 'string')
                .map((value) => value.trim())
                .filter((value) => value !== '');
            if (images.length < 2) {
                return;
            }

            const imageElement = card.querySelector('img[data-gallery-image]');
            if (!imageElement || imageElement.dataset.galleryReady === '1') {
                return;
            }
            imageElement.dataset.galleryReady = '1';

            let index = 0;
            const rotate = () => {
                index = (index + 1) % images.length;
                imageElement.classList.add('opacity-0');
                window.setTimeout(() => {
                    imageElement.src = images[index];
                    imageElement.classList.remove('opacity-0');
                }, 160);
            };

            const intervalId = window.setInterval(rotate, 2600);
            window.shopGalleryIntervals.push(intervalId);
        });
    };

    window.shopInitProductGallery = initGalleryCards;
    initGalleryCards(document);
})();

(() => {
    if (!window.fetch || !window.DOMParser || !window.history || !window.history.pushState) {
        return;
    }

    let root = document.querySelector('[data-shop-ajax-root]');
    if (!root) {
        return;
    }

    const normalizePath = (path) => path.replace(/\/+$/, '').toLowerCase();
    const isShopUrl = (url) => (
        url.origin === window.location.origin
        && normalizePath(url.pathname).endsWith('/shop.php')
    );

    const toAjaxUrl = (urlString) => {
        const url = new URL(urlString, window.location.href);
        url.searchParams.set('ajax', '1');
        return url;
    };

    const prefetchedUrls = window.shopPrefetchedUrls instanceof Set ? window.shopPrefetchedUrls : new Set();
    window.shopPrefetchedUrls = prefetchedUrls;

    const prefetchShopState = (urlString) => {
        const destination = new URL(urlString, window.location.href);
        if (!isShopUrl(destination)) {
            return;
        }

        const normalized = destination.toString();
        if (prefetchedUrls.has(normalized)) {
            return;
        }
        prefetchedUrls.add(normalized);

        void fetch(toAjaxUrl(normalized), {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).catch(() => {
        });
    };

    const scheduleNeighborPrefetch = () => {
        if (!root) {
            return;
        }
        const pagination = root.querySelector('nav.mt-xl');
        if (!pagination) {
            return;
        }

        const links = Array.from(pagination.querySelectorAll('a[href]'));
        const nextLink = links.find((link) => (
            link.querySelector('[data-icon="chevron_right"]')
            && !link.classList.contains('pointer-events-none')
        ));
        if (!(nextLink instanceof HTMLAnchorElement)) {
            return;
        }

        const runner = () => {
            prefetchShopState(nextLink.href);

            try {
                const nextUrl = new URL(nextLink.href, window.location.href);
                const pageValue = parseInt(nextUrl.searchParams.get('page') || '', 10);
                if (!Number.isNaN(pageValue) && pageValue > 0) {
                    nextUrl.searchParams.set('page', String(pageValue + 1));
                    prefetchShopState(nextUrl.toString());
                }
            } catch (error) {
            }
        };

        if (document.visibilityState === 'visible') {
            window.setTimeout(runner, 80);
            return;
        }

        if (typeof window.requestIdleCallback === 'function') {
            window.requestIdleCallback(runner, { timeout: 1000 });
            return;
        }
        window.setTimeout(runner, 260);
    };

    const setLoadingState = (isLoading) => {
        if (!root) {
            return;
        }
        root.classList.toggle('opacity-60', isLoading);
        root.classList.toggle('pointer-events-none', isLoading);
    };

    const updateFromResponse = (html) => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const nextRoot = doc.querySelector('[data-shop-ajax-root]');
        if (!nextRoot) {
            return false;
        }

        root.replaceWith(nextRoot);
        root = nextRoot;

        if (typeof window.shopInitProductGallery === 'function') {
            window.shopInitProductGallery(root);
        }
        scheduleNeighborPrefetch();
        return true;
    };

    const loadShopState = async (urlString, pushHistory = true, smoothScroll = true) => {
        const destination = new URL(urlString, window.location.href);
        if (!isShopUrl(destination)) {
            window.location.assign(destination.toString());
            return;
        }

        setLoadingState(true);
        try {
            const response = await fetch(toAjaxUrl(destination.toString()), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!response.ok) {
                throw new Error('Shop AJAX request failed.');
            }

            const html = await response.text();
            if (!updateFromResponse(html)) {
                throw new Error('Shop AJAX payload was invalid.');
            }

            if (pushHistory) {
                window.history.pushState({ shopAjax: true }, '', destination.toString());
            }
            if (smoothScroll) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        } catch (error) {
            window.location.assign(destination.toString());
        } finally {
            setLoadingState(false);
        }
    };

    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        const link = target.closest('a[href]');
        if (!(link instanceof HTMLAnchorElement) || !root.contains(link)) {
            return;
        }
        if (link.target === '_blank' || link.hasAttribute('download')) {
            return;
        }

        const url = new URL(link.href, window.location.href);
        if (!isShopUrl(url)) {
            return;
        }

        event.preventDefault();
        void loadShopState(url.toString(), true, true);
    }, true);

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !root.contains(form) || !form.matches('[data-shop-filter-form]')) {
            return;
        }

        event.preventDefault();
        const action = form.getAttribute('action') || 'shop.php';
        const url = new URL(action, window.location.href);
        if (!isShopUrl(url)) {
            form.submit();
            return;
        }

        const params = new URLSearchParams(new FormData(form));
        url.search = params.toString();
        void loadShopState(url.toString(), true, true);
    });

    window.addEventListener('popstate', () => {
        const current = new URL(window.location.href);
        if (!isShopUrl(current)) {
            return;
        }
        void loadShopState(current.toString(), false, false);
    });

    scheduleNeighborPrefetch();
})();
</script>
</body></html>
<?php endif; ?>

<?php

function shop_build_query(array $filters, array $override): string
{
    $merged = array_merge($filters, $override);
    $query = http_build_query([
        'q' => (string)($merged['q'] ?? ''),
        'category' => (string)($merged['category'] ?? ''),
        'sort' => (string)($merged['sort'] ?? 'newest'),
        'per_page' => (int)($merged['per_page'] ?? 12),
        'page' => (int)($merged['page'] ?? 1),
    ]);

    return $query === '' ? '' : '?' . $query;
}
