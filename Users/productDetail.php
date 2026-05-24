<?php

declare(strict_types=1);

$activePage = 'shop';

require_once __DIR__ . '/includes/shop_backend.php';
shop_start_session();

$slug = trim((string)($_GET['slug'] ?? ''));
$productId = max(0, (int)($_GET['id'] ?? 0));
$ajaxSection = strtolower(trim((string)($_GET['section'] ?? '')));
$isAjaxRequest = shop_is_ajax_request();

$dbError = null;
$product = null;
$cartPopup = null;
$pdo = null;
$productCacheHit = false;
$productCacheKey = shop_cache_key('product_detail', [
    'slug' => $slug,
    'id' => $productId,
    'v' => 1,
]);
$cachedProductPayload = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && shop_cache_fetch($productCacheKey, $cachedProductPayload)) {
    $productCacheHit = true;
    $product = is_array($cachedProductPayload) ? $cachedProductPayload : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = trim((string)($_POST['action'] ?? ''));
    if ($postAction === 'add_to_cart') {
        $addProductId = max(0, (int)($_POST['product_id'] ?? $productId));
        $addVariantId = max(0, (int)($_POST['variant_id'] ?? 0));
        $addQuantity = max(1, min(99, (int)($_POST['quantity'] ?? 1)));

        try {
            $pdo = shop_db();
            $added = shop_cart_add_item($pdo, $addProductId, $addQuantity, $addVariantId);
            shop_cart_set_flash($added);

            $redirectQuery = [];
            if ($slug !== '') {
                $redirectQuery['slug'] = $slug;
            } elseif ($productId > 0) {
                $redirectQuery['id'] = $productId;
            } elseif ($addProductId > 0) {
                $redirectQuery['id'] = $addProductId;
            }

            $redirect = 'productDetail.php';
            if (count($redirectQuery) > 0) {
                $redirect .= '?' . http_build_query($redirectQuery);
            }
            header('Location: ' . $redirect);
            exit;
        } catch (Throwable $exception) {
            $dbError = $exception->getMessage();
        }
    }
}

try {
    if (!$productCacheHit) {
        $pdo = shop_db();
        $product = shop_fetch_product_detail($pdo, $slug, $productId);
    }
} catch (Throwable $exception) {
    $dbError = $exception->getMessage();
}

if ($product === null && $dbError === null) {
    http_response_code(404);
}

if ($isAjaxRequest && $_SERVER['REQUEST_METHOD'] === 'GET' && $ajaxSection !== '') {
    if ($dbError !== null) {
        echo '<section class="rounded-xl border border-red-200 bg-error-container px-md py-sm text-sm text-on-error-container">DB connection error: ' . shop_h($dbError) . '</section>';
        exit;
    }

    if (!is_array($product)) {
        http_response_code(404);
        echo '<section class="rounded-xl bg-surface-container-lowest p-lg text-on-surface-variant">Product not found.</section>';
        exit;
    }

    if ($ajaxSection === 'related') {
        $relatedProducts = [];
        $relatedCacheKey = shop_cache_key('related_products', [
            'product_id' => (int)($product['id'] ?? 0),
            'category_id' => (int)($product['category_id'] ?? 0),
            'limit' => 4,
            'v' => 1,
        ]);
        if (!(shop_cache_fetch($relatedCacheKey, $relatedProducts) && is_array($relatedProducts))) {
            if (!$pdo instanceof PDO) {
                $pdo = shop_db();
            }
            $relatedProducts = shop_fetch_related_products(
                $pdo,
                (int)($product['id'] ?? 0),
                (int)($product['category_id'] ?? 0),
                4
            );
        }
        echo shop_render_related_products_markup($relatedProducts);
        exit;
    }

    if ($ajaxSection === 'reviews') {
        $reviews = [];
        $reviewsCacheKey = shop_cache_key('product_reviews', [
            'product_id' => (int)($product['id'] ?? 0),
            'limit' => 6,
            'v' => 1,
        ]);
        if (!(shop_cache_fetch($reviewsCacheKey, $reviews) && is_array($reviews))) {
            if (!$pdo instanceof PDO) {
                $pdo = shop_db();
            }
            $reviews = shop_fetch_product_reviews($pdo, (int)($product['id'] ?? 0), 6);
        }
        echo shop_render_reviews_markup($reviews);
        exit;
    }

    http_response_code(400);
    echo '<section class="rounded-xl bg-error-container px-md py-sm text-sm text-on-error-container">Invalid AJAX section.</section>';
    exit;
}

$cartPopup = shop_cart_pull_flash();

$images = is_array($product['images'] ?? null) ? $product['images'] : [];
$images = array_values(array_filter(array_map(
    static fn (mixed $value): string => trim((string)$value),
    $images
), static fn (string $value): bool => $value !== ''));
$images = array_values(array_unique($images));
$variants = is_array($product['variants'] ?? null) ? $product['variants'] : [];
$productName = trim((string)($product['name'] ?? 'Product'));
$primaryImage = $images[0] ?? '';
$description = trim((string)($product['description'] ?? ''));
if ($description === '') {
    $description = trim((string)($product['short_description'] ?? ''));
}

$price = (float)($product['price'] ?? 0);
$comparePriceRaw = $product['compare_price'] ?? null;
$comparePrice = $comparePriceRaw !== null ? (float)$comparePriceRaw : null;
$stockTotal = (int)($product['stock_quantity'] ?? 0);
$inStock = $stockTotal > 0;
$maxQuantity = $inStock ? max(1, min(99, $stockTotal)) : 1;

if (count($variants) > 0) {
    $firstVariant = $variants[0];
    $price = (float)($firstVariant['price'] ?? $price);
    $variantComparePrice = $firstVariant['compare_price'] ?? null;
    $variantStock = (int)($firstVariant['stock_quantity'] ?? 0);
    if ($variantStock > 0) {
        $maxQuantity = max(1, min(99, $variantStock));
    }
    if ($variantComparePrice !== null) {
        $comparePrice = (float)$variantComparePrice;
    }
}

$defaultVariantId = count($variants) > 0 ? max(0, (int)($variants[0]['id'] ?? 0)) : 0;

$detailQuery = [];
if ($slug !== '') {
    $detailQuery['slug'] = $slug;
} elseif ($productId > 0) {
    $detailQuery['id'] = $productId;
}
$ajaxQueryBase = $detailQuery;
$ajaxQueryBase['ajax'] = '1';
$relatedAjaxUrl = 'productDetail.php?' . http_build_query(array_merge($ajaxQueryBase, ['section' => 'related']));
$reviewsAjaxUrl = 'productDetail.php?' . http_build_query(array_merge($ajaxQueryBase, ['section' => 'reviews']));

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= shop_h($productName) ?> | LuvShop</title>
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
        #qty-input::-webkit-outer-spin-button,
        #qty-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        #qty-input[type="number"] {
            -moz-appearance: textfield;
            appearance: textfield;
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

    <?php if (!is_array($product)): ?>
        <section class="bg-surface-container-lowest rounded-2xl p-xl soft-shadow text-center">
            <h1 class="text-headline-lg font-headline-lg text-primary mb-sm">Product not found</h1>
            <p class="text-on-surface-variant mb-lg">The product may be inactive or removed.</p>
            <a class="inline-flex items-center gap-sm px-lg py-sm bg-primary text-on-primary rounded-full font-label-md" href="shop.php">
                <span class="material-symbols-outlined">arrow_back</span>
                Back to Shop
            </a>
        </section>
    <?php else: ?>
        <nav class="mb-lg text-sm text-on-surface-variant flex items-center gap-sm">
            <a class="hover:text-primary" href="Home.php">Home</a>
            <span>/</span>
            <a class="hover:text-primary" href="shop.php">Shop</a>
            <span>/</span>
            <span class="text-primary"><?= shop_h($productName) ?></span>
        </nav>

        <section class="grid grid-cols-1 lg:grid-cols-12 gap-lg mb-xl">
            <div class="lg:col-span-6 space-y-md">
                <div class="aspect-square rounded-[2rem] overflow-hidden bg-surface-container-low soft-shadow border border-outline-variant/30">
                    <?php if ($primaryImage !== ''): ?>
                        <img alt="<?= shop_h($productName) ?>" class="w-full h-full object-cover" data-gallery-main id="product-main-image" src="<?= shop_h($primaryImage) ?>"/>
                    <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-br from-rose-100 to-pink-200 flex items-center justify-center">
                            <span class="text-5xl text-primary font-bold"><?= shop_h(strtoupper(substr($productName, 0, 1))) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (count($images) > 1): ?>
                    <div class="flex items-center gap-sm">
                        <button aria-label="Previous image" class="h-10 w-10 rounded-full border border-outline-variant/40 bg-white text-on-surface-variant hover:bg-surface-container-low disabled:opacity-30 disabled:cursor-not-allowed" id="gallery-prev" type="button">
                            <span class="material-symbols-outlined">chevron_left</span>
                        </button>
                        <div class="flex-1 overflow-hidden">
                            <div class="flex items-center gap-sm overflow-x-auto scroll-smooth px-xs py-xs [&::-webkit-scrollbar]:hidden [scrollbar-width:none]" id="gallery-track">
                                <?php foreach ($images as $imageIndex => $image): ?>
                                    <button aria-label="View image <?= (int)$imageIndex + 1 ?>" class="w-20 sm:w-24 aspect-[4/5] rounded-xl overflow-hidden border transition-all duration-200 flex-shrink-0 <?= $imageIndex === 0 ? 'border-primary ring-2 ring-primary/20' : 'border-outline-variant/40 hover:border-primary/50' ?>" data-gallery-src="<?= shop_h($image) ?>" data-gallery-thumb type="button">
                                        <img alt="<?= shop_h($productName) ?>" class="w-full h-full object-cover" decoding="async" loading="lazy" src="<?= shop_h($image) ?>"/>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <button aria-label="Next image" class="h-10 w-10 rounded-full border border-outline-variant/40 bg-white text-on-surface-variant hover:bg-surface-container-low disabled:opacity-30 disabled:cursor-not-allowed" id="gallery-next" type="button">
                            <span class="material-symbols-outlined">chevron_right</span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <div class="lg:col-span-6 bg-surface-container-lowest rounded-2xl p-lg soft-shadow">
                <div class="flex items-center gap-sm mb-md">
                    <span class="px-md py-xs rounded-full text-label-sm font-label-sm <?= $inStock ? 'bg-secondary-container text-on-secondary-container' : 'bg-error-container text-on-error-container' ?>">
                        <?= $inStock ? 'In Stock' : 'Out of Stock' ?>
                    </span>
                    <?php if (trim((string)($product['category_name'] ?? '')) !== ''): ?>
                        <span class="px-md py-xs rounded-full text-label-sm font-label-sm bg-tertiary-container text-on-tertiary-container">
                            <?= shop_h((string)$product['category_name']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <h1 class="text-headline-lg font-headline-lg text-primary mb-sm"><?= shop_h($productName) ?></h1>

                <?php if (trim((string)($product['brand_name'] ?? '')) !== ''): ?>
                    <p class="text-on-surface-variant mb-md">Brand: <span class="font-semibold"><?= shop_h((string)$product['brand_name']) ?></span></p>
                <?php endif; ?>

                <p class="text-body-md text-on-surface-variant leading-relaxed mb-lg">
                    <?= shop_h($description !== '' ? $description : 'No description yet for this product.') ?>
                </p>

                <?php if (count($variants) > 0): ?>
                    <div class="mb-lg">
                        <h2 class="text-label-md font-label-md text-primary mb-sm">Available Variants</h2>
                        <div class="space-y-sm max-h-52 overflow-auto pr-1">
                            <?php foreach ($variants as $variant): ?>
                                <?php
                                    $variantName = trim((string)($variant['variant_name'] ?? 'Default Variant'));
                                    $variantSku = trim((string)($variant['sku'] ?? ''));
                                    $variantPrice = (float)($variant['price'] ?? 0);
                                    $variantStock = (int)($variant['stock_quantity'] ?? 0);
                                ?>
                                <div class="flex items-center justify-between py-xs">
                                    <div>
                                        <p class="font-semibold text-on-surface"><?= shop_h($variantName) ?></p>
                                        <p class="text-xs text-on-surface-variant">
                                            <?= $variantSku !== '' ? 'SKU: ' . shop_h($variantSku) : 'SKU unavailable' ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold"><?= shop_h(shop_money($variantPrice)) ?></p>
                                        <p class="text-xs <?= $variantStock > 0 ? 'text-on-surface-variant' : 'text-on-error-container' ?>">
                                            <?= $variantStock > 0 ? $variantStock . ' available' : 'Out of stock' ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <input name="action" type="hidden" value="add_to_cart"/>
                    <input name="product_id" type="hidden" value="<?= (int)($product['id'] ?? 0) ?>"/>
                    <input name="variant_id" type="hidden" value="<?= (int)$defaultVariantId ?>"/>

                    <div class="mb-md">
                        <label class="block text-label-md font-label-md text-primary mb-xs" for="qty-input">Quantity</label>
                        <div class="inline-flex items-center rounded-xl border border-outline-variant/50 overflow-hidden bg-surface-container-low">
                            <button class="w-10 h-10 flex items-center justify-center text-on-surface hover:bg-surface-container-high disabled:opacity-40 disabled:cursor-not-allowed" id="qty-decrease" type="button" <?= $inStock ? '' : 'disabled' ?>>-</button>
                            <input class="w-14 h-10 text-center bg-white border-x border-outline-variant/40 outline-none" id="qty-input" max="<?= (int)$maxQuantity ?>" min="1" name="quantity" type="number" value="1" <?= $inStock ? '' : 'disabled' ?>/>
                            <button class="w-10 h-10 flex items-center justify-center text-on-surface hover:bg-surface-container-high disabled:opacity-40 disabled:cursor-not-allowed" id="qty-increase" type="button" <?= $inStock ? '' : 'disabled' ?>>+</button>
                        </div>
                        <?php if ($inStock): ?>
                            <p class="mt-xs text-xs text-on-surface-variant">Max <?= (int)$maxQuantity ?> for selected stock.</p>
                        <?php endif; ?>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-sm">
                        <button class="w-full sm:w-auto px-xl py-md rounded-full font-label-md flex items-center justify-center gap-sm inner-glow <?= $inStock ? 'bg-primary text-on-primary hover:opacity-90 active:scale-95' : 'bg-surface-container text-on-surface-variant cursor-not-allowed' ?>" type="submit" <?= $inStock ? '' : 'disabled' ?>>
                            <span class="material-symbols-outlined">add_shopping_cart</span>
                            Add to Cart
                        </button>
                    </div>
                </form>
                <div class="flex flex-col sm:flex-row gap-sm mt-sm">
                    <a class="w-full sm:w-auto px-xl py-md rounded-full font-label-md border border-outline-variant text-on-surface hover:bg-surface-container-low text-center" href="shop.php">
                        Continue Shopping
                    </a>
                </div>
            </div>
        </section>

        <section class="mb-lg rounded-2xl bg-surface-container-lowest p-lg soft-shadow">
            <div class="flex items-center justify-between mb-md">
                <h2 class="text-headline-md font-headline-md text-primary">Customer Reviews</h2>
            </div>
            <div data-lazy-section data-lazy-url="<?= shop_h($reviewsAjaxUrl) ?>" id="product-reviews-lazy">
                <div class="animate-pulse space-y-sm">
                    <div class="h-4 w-40 rounded bg-surface-container-high"></div>
                    <div class="h-4 w-full rounded bg-surface-container-high"></div>
                    <div class="h-4 w-10/12 rounded bg-surface-container-high"></div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl bg-surface-container-lowest p-lg soft-shadow">
            <div class="flex items-center justify-between mb-md">
                <h2 class="text-headline-md font-headline-md text-primary">You Might Also Like</h2>
                <a class="text-primary font-label-md hover:opacity-80" href="shop.php">View All</a>
            </div>
            <div data-lazy-section data-lazy-url="<?= shop_h($relatedAjaxUrl) ?>" id="related-products-lazy">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-md">
                    <?php for ($placeholderIndex = 0; $placeholderIndex < 4; $placeholderIndex++): ?>
                        <div class="rounded-xl p-md bg-surface-container-low animate-pulse">
                            <div class="aspect-square rounded-xl bg-surface-container-high mb-sm"></div>
                            <div class="h-4 rounded bg-surface-container-high mb-xs"></div>
                            <div class="h-4 w-20 rounded bg-surface-container-high"></div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../Templates/footer.php'; ?>
<script>
    (function () {
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

    (function () {
        const mainImage = document.querySelector('[data-gallery-main]');
        const thumbnailButtons = Array.from(document.querySelectorAll('[data-gallery-thumb]'));
        const prevButton = document.getElementById('gallery-prev');
        const nextButton = document.getElementById('gallery-next');

        if (!mainImage || thumbnailButtons.length === 0) {
            return;
        }

        let activeIndex = 0;

        const setActiveThumbStyles = () => {
            thumbnailButtons.forEach((button, index) => {
                if (index === activeIndex) {
                    button.classList.add('border-primary', 'ring-2', 'ring-primary/20');
                    button.classList.remove('border-outline-variant/40', 'hover:border-primary/50');
                } else {
                    button.classList.remove('border-primary', 'ring-2', 'ring-primary/20');
                    button.classList.add('border-outline-variant/40', 'hover:border-primary/50');
                }
            });
        };

        const updateArrowState = () => {
            if (prevButton) {
                prevButton.disabled = activeIndex <= 0;
            }
            if (nextButton) {
                nextButton.disabled = activeIndex >= thumbnailButtons.length - 1;
            }
        };

        const activateImage = (index, shouldScrollThumb = true) => {
            const safeIndex = Math.max(0, Math.min(thumbnailButtons.length - 1, index));
            activeIndex = safeIndex;

            const activeButton = thumbnailButtons[activeIndex];
            const imageSrc = activeButton.dataset.gallerySrc || '';
            if (imageSrc !== '') {
                mainImage.src = imageSrc;
            }

            setActiveThumbStyles();
            updateArrowState();

            if (shouldScrollThumb) {
                activeButton.scrollIntoView({
                    behavior: 'smooth',
                    inline: 'center',
                    block: 'nearest'
                });
            }
        };

        thumbnailButtons.forEach((button, index) => {
            button.addEventListener('click', () => activateImage(index, false));
        });

        if (prevButton) {
            prevButton.addEventListener('click', () => activateImage(activeIndex - 1));
        }
        if (nextButton) {
            nextButton.addEventListener('click', () => activateImage(activeIndex + 1));
        }

        activateImage(0, false);
    })();

    (function () {
        const qtyInput = document.getElementById('qty-input');
        const decrease = document.getElementById('qty-decrease');
        const increase = document.getElementById('qty-increase');

        if (!qtyInput || !decrease || !increase) {
            return;
        }

        const getBounds = () => ({
            min: Math.max(1, parseInt(qtyInput.min || '1', 10) || 1),
            max: Math.max(1, parseInt(qtyInput.max || '99', 10) || 99)
        });

        const clamp = (value) => {
            const bounds = getBounds();
            return Math.min(bounds.max, Math.max(bounds.min, value));
        };

        const normalizeInput = () => {
            const raw = parseInt(qtyInput.value || '1', 10);
            qtyInput.value = String(clamp(Number.isNaN(raw) ? 1 : raw));
        };

        decrease.addEventListener('click', () => {
            const raw = parseInt(qtyInput.value || '1', 10);
            const current = Number.isNaN(raw) ? 1 : raw;
            qtyInput.value = String(clamp(current - 1));
        });

        increase.addEventListener('click', () => {
            const raw = parseInt(qtyInput.value || '1', 10);
            const current = Number.isNaN(raw) ? 1 : raw;
            qtyInput.value = String(clamp(current + 1));
        });

        qtyInput.addEventListener('input', normalizeInput);
        qtyInput.addEventListener('blur', normalizeInput);
        normalizeInput();
    })();

    (function () {
        if (!window.fetch) {
            return;
        }

        const sections = Array.from(document.querySelectorAll('[data-lazy-section][data-lazy-url]'));
        if (sections.length === 0) {
            return;
        }

        const loadSection = async (container) => {
            const url = container.getAttribute('data-lazy-url');
            if (!url) {
                return;
            }

            try {
                const response = await fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (!response.ok) {
                    throw new Error('Lazy section request failed');
                }

                container.innerHTML = await response.text();
            } catch (error) {
                container.innerHTML = '<section class="rounded-xl bg-surface-container-low px-md py-sm text-on-surface-variant text-sm">Unable to load this section right now.</section>';
            }
        };

        sections.forEach((container, index) => {
            const run = () => {
                void loadSection(container);
            };

            if (typeof window.requestIdleCallback === 'function') {
                window.requestIdleCallback(run, { timeout: 1400 + (index * 500) });
                return;
            }

            window.setTimeout(run, 260 + (index * 240));
        });
    })();
</script>
</body>
</html>

<?php

function shop_render_related_products_markup(array $relatedProducts): string
{
    if (count($relatedProducts) === 0) {
        return '<section class="rounded-xl bg-surface-container-low px-md py-sm text-on-surface-variant text-sm">No related products available yet.</section>';
    }

    ob_start();
    ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-md">
        <?php foreach ($relatedProducts as $index => $related): ?>
            <?php
                $relatedName = trim((string)($related['name'] ?? 'Product'));
                $relatedImage = trim((string)($related['image'] ?? ''));
                $relatedPrice = (float)($related['price'] ?? 0);
                $relatedLink = shop_product_detail_url($related);
            ?>
            <a class="group bg-surface-container-lowest rounded-xl p-md soft-shadow hover:-translate-y-1 transition-all" href="<?= shop_h($relatedLink) ?>">
                <div class="aspect-square rounded-xl overflow-hidden mb-sm bg-surface-container-low">
                    <?php if ($relatedImage !== ''): ?>
                        <img alt="<?= shop_h($relatedName) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform" decoding="async" loading="lazy" src="<?= shop_h($relatedImage) ?>"/>
                    <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-br <?= shop_h(shop_placeholder_gradient($index)) ?> flex items-center justify-center">
                            <span class="text-primary font-semibold"><?= shop_h(strtoupper(substr($relatedName, 0, 1))) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <h3 class="font-semibold text-primary mb-xs"><?= shop_h($relatedName) ?></h3>
                <p class="font-bold"><?= shop_h(shop_money($relatedPrice)) ?></p>
            </a>
        <?php endforeach; ?>
    </div>
    <?php
    return (string)ob_get_clean();
}

function shop_render_reviews_markup(array $reviews): string
{
    if (count($reviews) === 0) {
        return '<section class="rounded-xl bg-surface-container-low px-md py-sm text-on-surface-variant text-sm">No reviews yet. Be the first to review this product.</section>';
    }

    ob_start();
    ?>
    <div class="space-y-md">
        <?php foreach ($reviews as $review): ?>
            <?php
                $reviewer = trim((string)($review['reviewer_name'] ?? 'Anonymous'));
                $comment = trim((string)($review['comment'] ?? ''));
                $rating = max(0, min(5, (int)($review['rating'] ?? 0)));
                $createdAt = trim((string)($review['created_at'] ?? ''));
                $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
                $dateLabel = '';
                if ($createdAt !== '') {
                    $timestamp = strtotime($createdAt);
                    if ($timestamp !== false) {
                        $dateLabel = date('M j, Y', $timestamp);
                    }
                }
            ?>
            <article class="rounded-xl border border-outline-variant/30 bg-surface p-md">
                <div class="flex flex-wrap items-center justify-between gap-sm mb-xs">
                    <p class="font-semibold text-on-surface"><?= shop_h($reviewer) ?></p>
                    <div class="text-sm text-primary tracking-wide"><?= shop_h($stars) ?></div>
                </div>
                <?php if ($comment !== ''): ?>
                    <p class="text-on-surface-variant text-sm leading-relaxed"><?= nl2br(shop_h($comment)) ?></p>
                <?php else: ?>
                    <p class="text-on-surface-variant text-sm">No comment provided.</p>
                <?php endif; ?>
                <?php if ($dateLabel !== ''): ?>
                    <p class="text-xs text-on-surface-variant mt-sm"><?= shop_h($dateLabel) ?></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
    return (string)ob_get_clean();
}
