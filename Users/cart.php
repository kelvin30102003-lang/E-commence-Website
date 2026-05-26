<?php

declare(strict_types=1);

$activePage = 'cart';

require_once __DIR__ . '/includes/shop_backend.php';
shop_start_session();

$isDrawer = isset($_GET['drawer']) && (string)$_GET['drawer'] === '1';
$dbError = null;
$cart = [
    'items' => [],
    'subtotal' => 0.0,
    'item_count' => 0,
    'line_count' => 0,
];

try {
    $pdo = shop_db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = trim((string)($_POST['action'] ?? ''));
        if ($action === 'update_qty') {
            $lineKey = trim((string)($_POST['line_key'] ?? ''));
            $quantity = max(0, (int)($_POST['quantity'] ?? 0));
            shop_cart_set_quantity($pdo, $lineKey, $quantity);
        } elseif ($action === 'remove_line') {
            $lineKey = trim((string)($_POST['line_key'] ?? ''));
            shop_cart_remove_line($lineKey);
        } elseif ($action === 'clear_cart') {
            shop_cart_clear();
        }

        header('Location: cart.php' . ($isDrawer ? '?drawer=1' : ''));
        exit;
    }

    $cart = shop_cart_details($pdo);
} catch (Throwable $exception) {
    $dbError = $exception->getMessage();
}

$isLoggedIn = shop_user_is_logged_in();
$checkoutHref = $isLoggedIn
    ? 'checkout.php' . ($isDrawer ? '?drawer=1' : '')
    : shop_login_url('../Users/Home.php?open_cart=checkout');

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Your Cart | LuvShop</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&amp;family=Plus+Jakarta+Sans:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .soft-shadow {
            box-shadow: 0 10px 30px -5px rgba(120, 85, 94, 0.08);
        }
        .qty-input::-webkit-outer-spin-button,
        .qty-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .qty-input[type="number"] {
            -moz-appearance: textfield;
            appearance: textfield;
        }
        .empty-cart-illustration {
            width: min(260px, 72vw);
            height: auto;
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
                }
            }
        }
    </script>
</head>
<body class="bg-background text-on-surface font-body-md overflow-x-hidden">

<main class="<?= $isDrawer ? 'min-h-screen px-6 py-8' : 'min-h-screen pb-xl pt-xl px-4 md:px-margin-desktop max-w-[1280px] mx-auto' ?>" id="app-main">
    <?php if ($dbError !== null): ?>
        <section class="mb-lg rounded-xl border border-red-200 bg-error-container px-md py-sm text-sm text-on-error-container">
            DB connection error: <?= shop_h($dbError) ?>
        </section>
    <?php endif; ?>

    <?php if (count($cart['items']) === 0): ?>
        <section class="<?= $isDrawer ? 'min-h-[calc(100vh-4rem)]' : 'min-h-[58vh]' ?> flex flex-col items-center justify-center text-center px-md py-xl">
            <img class="empty-cart-illustration mb-lg" src="../Assect/images/empty-cart.svg" alt="Empty shopping cart"/>
            <p class="text-[22px] leading-8 font-body-md text-black">No products in the cart.</p>
        </section>
    <?php else: ?>
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-lg">
            <div class="lg:col-span-8 space-y-md">
                <?php foreach ($cart['items'] as $item): ?>
                    <article class="bg-surface-container-lowest rounded-2xl p-md soft-shadow border border-outline-variant/20 flex flex-col sm:flex-row sm:items-center gap-md">
                        <a class="w-full sm:w-28 h-28 rounded-xl overflow-hidden bg-surface-container-low flex-shrink-0" data-ajax="true" href="<?= shop_h((string)$item['detail_url']) ?>">
                            <?php if ((string)$item['image'] !== ''): ?>
                                <img alt="<?= shop_h((string)$item['product_name']) ?>" class="w-full h-full object-cover" decoding="async" loading="lazy" src="<?= shop_h((string)$item['image']) ?>"/>
                            <?php else: ?>
                                <div class="w-full h-full bg-gradient-to-br from-rose-100 to-pink-200 flex items-center justify-center">
                                    <span class="text-primary font-bold"><?= shop_h(strtoupper(substr((string)$item['product_name'], 0, 1))) ?></span>
                                </div>
                            <?php endif; ?>
                        </a>

                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-semibold text-primary leading-snug break-words"><?= shop_h((string)$item['product_name']) ?></h3>
                            <?php if (trim((string)$item['variant_name']) !== ''): ?>
                                <p class="text-sm text-on-surface-variant">Variant: <?= shop_h((string)$item['variant_name']) ?></p>
                            <?php endif; ?>
                            <?php if (trim((string)$item['sku']) !== ''): ?>
                                <p class="text-xs text-on-surface-variant">SKU: <?= shop_h((string)$item['sku']) ?></p>
                            <?php endif; ?>
                            <p class="text-sm font-semibold mt-xs"><?= shop_h(shop_money((float)$item['unit_price'])) ?> each</p>
                        </div>

                        <div class="flex items-center gap-sm">
                            <form class="flex items-center gap-sm" data-qty-form method="post">
                                <input data-cart-action name="action" type="hidden" value="update_qty"/>
                                <input name="line_key" type="hidden" value="<?= shop_h((string)$item['line_key']) ?>"/>
                                <input data-qty-hidden name="quantity" type="hidden" value="<?= (int)$item['quantity'] ?>"/>
                                <div class="inline-flex items-center rounded-xl border border-outline-variant/50 overflow-hidden bg-surface-container-low">
                                    <?php if ((int)$item['quantity'] <= 1): ?>
                                        <button class="w-10 h-10 flex items-center justify-center text-on-surface hover:bg-surface-container-high disabled:opacity-40 disabled:cursor-not-allowed" data-qty-minus name="action" value="remove_line" type="submit">-</button>
                                    <?php else: ?>
                                        <button class="w-10 h-10 flex items-center justify-center text-on-surface hover:bg-surface-container-high disabled:opacity-40 disabled:cursor-not-allowed" data-qty-minus name="quantity" value="<?= max(1, (int)$item['quantity'] - 1) ?>" type="submit">-</button>
                                    <?php endif; ?>
                                    <input class="qty-input w-14 h-10 text-center bg-white border-x border-outline-variant/40 outline-none" data-qty-input max="<?= (int)$item['stock_quantity'] ?>" min="1" type="number" value="<?= (int)$item['quantity'] ?>"/>
                                    <button class="w-10 h-10 flex items-center justify-center text-on-surface hover:bg-surface-container-high disabled:opacity-40 disabled:cursor-not-allowed" data-qty-plus name="quantity" value="<?= min((int)$item['stock_quantity'], (int)$item['quantity'] + 1) ?>" type="submit">+</button>
                                </div>
                            </form>
                        </div>

                        <div class="text-right sm:min-w-[120px]">
                            <p class="text-sm text-on-surface-variant">Line Total</p>
                            <p class="text-lg font-bold text-on-surface"><?= shop_h(shop_money((float)$item['line_total'])) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <aside class="lg:col-span-4">
                <div class="bg-surface-container-lowest rounded-2xl p-lg soft-shadow border border-outline-variant/20 sticky top-24">
                    <h2 class="text-headline-md font-headline-md text-primary mb-md">Summary</h2>
                    <div class="space-y-sm text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-on-surface-variant">Subtotal</span>
                            <span class="font-semibold text-on-surface"><?= shop_h(shop_money((float)$cart['subtotal'])) ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-on-surface-variant">Shipping</span>
                            <span class="font-semibold text-on-surface-variant">Calculated at checkout</span>
                        </div>
                    </div>
                    <div class="mt-md pt-md border-t border-outline-variant/30 flex items-center justify-between">
                        <span class="font-semibold">Estimated Total</span>
                        <span class="text-xl font-bold text-on-surface"><?= shop_h(shop_money((float)$cart['subtotal'])) ?></span>
                    </div>
                    <a class="mt-lg w-full inline-flex justify-center items-center rounded-full bg-primary text-on-primary py-md font-semibold hover:opacity-90" data-checkout-link <?= $isLoggedIn ? '' : 'data-requires-login="true" ' ?>href="<?= shop_h($checkoutHref) ?>"<?= (!$isLoggedIn && $isDrawer) ? ' target="_top"' : '' ?>>
                        Checkout
                    </a>
                    <form class="mt-sm" method="post" onsubmit="return confirm('Clear all items from cart?');">
                        <input name="action" type="hidden" value="clear_cart"/>
                        <button class="w-full rounded-full border border-outline-variant py-sm text-on-surface hover:bg-surface-container-low" type="submit">Clear Cart</button>
                    </form>
                </div>
            </aside>
        </section>
    <?php endif; ?>
</main>

<script>
(() => {
    const notifyCartCount = () => {
        if (window.parent && window.parent !== window) {
            window.parent.postMessage({
                type: 'luvshop:cart-count',
                count: <?= (int)$cart['item_count'] ?>
            }, window.location.origin);
        }
    };

    notifyCartCount();

    const checkoutReturnUrl = () => {
        let parentUrl = '../Users/Home.php?open_cart=checkout';
        try {
            if (window.parent && window.parent !== window && window.parent.location) {
                const url = new URL(window.parent.location.href);
                url.searchParams.set('open_cart', 'checkout');
                parentUrl = url.toString();
            }
        } catch (_error) {
        }
        return parentUrl;
    };

    document.querySelectorAll('[data-checkout-link]').forEach((link) => {
        link.addEventListener('click', (event) => {
            if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            if (link.dataset.requiresLogin === 'true' && window.top && window.top !== window) {
                event.preventDefault();
                const loginUrl = new URL(link.href, window.location.href);
                loginUrl.searchParams.set('redirect', checkoutReturnUrl());
                window.top.location.href = loginUrl.toString();
            }
        });
    });

    const forms = document.querySelectorAll('[data-qty-form]');
    forms.forEach((form) => {
        const input = form.querySelector('[data-qty-input]');
        const hiddenQty = form.querySelector('[data-qty-hidden]');
        const minus = form.querySelector('[data-qty-minus]');
        const plus = form.querySelector('[data-qty-plus]');
        const action = form.querySelector('[data-cart-action]');
        if (!input || !hiddenQty || !minus || !plus || !action) {
            return;
        }

        const getBounds = () => {
            const min = Math.max(1, parseInt(input.min || '1', 10) || 1);
            const max = Math.max(min, parseInt(input.max || '99', 10) || 99);
            return { min, max };
        };

        const clamp = (value) => {
            const bounds = getBounds();
            return Math.min(bounds.max, Math.max(bounds.min, value));
        };

        const normalize = () => {
            const raw = parseInt(input.value || '1', 10);
            const value = clamp(Number.isNaN(raw) ? 1 : raw);
            input.value = String(value);
            hiddenQty.value = String(value);
            return value;
        };

        let isSubmitting = false;
        input.dataset.lastQty = String(normalize());

        const submitIfChanged = () => {
            if (isSubmitting) {
                return;
            }

            const current = normalize();
            const previous = parseInt(input.dataset.lastQty || String(current), 10) || current;
            if (current === previous) {
                return;
            }

            input.dataset.lastQty = String(current);
            hiddenQty.value = String(current);
            action.value = 'update_qty';
            isSubmitting = true;
            form.submit();
        };

        minus.addEventListener('click', (event) => {
            event.preventDefault();
            const current = normalize();
            if (current <= 1) {
                action.value = 'remove_line';
                hiddenQty.value = '0';
                isSubmitting = true;
                form.submit();
                return;
            }

            const next = clamp(current - 1);
            if (next !== current) {
                input.value = String(next);
                hiddenQty.value = String(next);
                submitIfChanged();
            }
        });

        plus.addEventListener('click', (event) => {
            event.preventDefault();
            const current = normalize();
            const next = clamp(current + 1);
            if (next !== current) {
                input.value = String(next);
                hiddenQty.value = String(next);
                submitIfChanged();
            }
        });

        input.addEventListener('change', submitIfChanged);
        input.addEventListener('blur', submitIfChanged);
    });
})();
</script>
</body>
</html>
