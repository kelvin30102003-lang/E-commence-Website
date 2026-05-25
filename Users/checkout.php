<?php

declare(strict_types=1);

$activePage = 'shop';

require_once __DIR__ . '/includes/shop_backend.php';
shop_start_session();
shop_require_checkout_login();

$dbError = null;
$checkoutError = null;
$successOrder = null;

$currentUser = shop_current_user();
$form = shop_checkout_default_form();
if (is_array($currentUser)) {
    if ((string)($currentUser['name'] ?? '') !== '') {
        $form['full_name'] = (string)$currentUser['name'];
    }
    if ((string)($currentUser['email'] ?? '') !== '') {
        $form['email'] = (string)$currentUser['email'];
    }
}
$paymentMethods = shop_payment_methods();
$shippingMethods = shop_shipping_methods();

$cart = [
    'items' => [],
    'subtotal' => 0.0,
    'item_count' => 0,
    'line_count' => 0,
];

try {
    $pdo = shop_db();
    $cart = shop_cart_details($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($form as $key => $defaultValue) {
            if (array_key_exists($key, $_POST)) {
                $form[$key] = trim((string)$_POST[$key]);
            }
        }

        try {
            $successOrder = shop_place_order($pdo, $form);
            $form = shop_checkout_default_form();
            $cart = shop_cart_details($pdo);
        } catch (Throwable $exception) {
            $checkoutError = $exception->getMessage();
            $cart = shop_cart_details($pdo);
        }
    }
} catch (Throwable $exception) {
    $dbError = $exception->getMessage();
}

$selectedShippingMethod = $form['shipping_method'];
if (!isset($shippingMethods[$selectedShippingMethod])) {
    $selectedShippingMethod = 'standard';
    $form['shipping_method'] = $selectedShippingMethod;
}
$shippingPreview = $shippingMethods[$selectedShippingMethod];
$shippingFeePreview = (float)($shippingPreview['fee'] ?? 0.0);
$estimatedTotal = round((float)$cart['subtotal'] + $shippingFeePreview, 2);

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Checkout | LuvShop</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&amp;family=Plus+Jakarta+Sans:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .soft-shadow {
            box-shadow: 0 10px 30px -5px rgba(120, 85, 94, 0.08);
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

<main class="min-h-screen pb-xl pt-xl px-4 md:px-margin-desktop max-w-[1280px] mx-auto" id="app-main">
    <?php if ($dbError !== null): ?>
        <section class="mb-lg rounded-xl border border-red-200 bg-error-container px-md py-sm text-sm text-on-error-container">
            DB connection error: <?= shop_h($dbError) ?>
        </section>
    <?php endif; ?>

    <?php if (is_array($successOrder)): ?>
        <section class="bg-surface-container-lowest rounded-2xl p-xl soft-shadow border border-secondary-container">
            <div class="flex items-center gap-sm text-secondary font-semibold mb-sm">
                <span class="material-symbols-outlined">check_circle</span>
                Order placed successfully
            </div>
            <h1 class="text-headline-lg font-headline-lg text-primary mb-sm">Thank you for your order!</h1>
            <p class="text-on-surface-variant mb-lg">Your order number is <span class="font-semibold text-on-surface">#<?= shop_h((string)$successOrder['order_number']) ?></span>.</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-sm mb-lg">
                <div class="rounded-xl bg-surface-container-low p-md">
                    <p class="text-xs text-on-surface-variant">Order ID</p>
                    <p class="font-semibold">#<?= (int)$successOrder['order_id'] ?></p>
                </div>
                <div class="rounded-xl bg-surface-container-low p-md">
                    <p class="text-xs text-on-surface-variant">Payment</p>
                    <p class="font-semibold"><?= shop_h(strtoupper((string)$successOrder['payment_method'])) ?></p>
                </div>
                <div class="rounded-xl bg-surface-container-low p-md">
                    <p class="text-xs text-on-surface-variant">Total</p>
                    <p class="font-semibold"><?= shop_h(shop_money((float)$successOrder['total_amount'])) ?></p>
                </div>
            </div>
            <div class="flex flex-wrap gap-sm">
                <a class="px-lg py-sm rounded-full bg-primary text-on-primary font-semibold" href="shop.php">Continue Shopping</a>
                <a class="px-lg py-sm rounded-full border border-outline-variant text-on-surface" href="cart.php">Back to Cart</a>
            </div>
        </section>
    <?php elseif (count($cart['items']) === 0): ?>
        <section class="bg-surface-container-lowest rounded-2xl p-xl soft-shadow text-center">
            <h1 class="text-headline-lg font-headline-lg text-primary mb-sm">No items to checkout</h1>
            <p class="text-on-surface-variant mb-lg">Your cart is empty. Add products before checkout.</p>
            <a class="inline-flex px-lg py-sm rounded-full bg-primary text-on-primary font-label-md" href="shop.php">Browse Products</a>
        </section>
    <?php else: ?>
        <section class="mb-md">
            <h1 class="text-headline-lg font-headline-lg text-primary">Checkout</h1>
            <p class="text-on-surface-variant">Select payment, shipment, and delivery details.</p>
        </section>

        <?php if ($checkoutError !== null): ?>
            <section class="mb-md rounded-xl border border-red-200 bg-error-container px-md py-sm text-sm text-on-error-container">
                <?= shop_h($checkoutError) ?>
            </section>
        <?php endif; ?>

        <section class="grid grid-cols-1 lg:grid-cols-12 gap-lg">
            <form class="lg:col-span-8 bg-surface-container-lowest rounded-2xl p-lg soft-shadow border border-outline-variant/20 space-y-md" method="post">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                    <div>
                        <label class="block text-sm font-semibold mb-xs" for="full_name">Full Name</label>
                        <input class="w-full rounded-xl border-outline-variant" id="full_name" maxlength="150" name="full_name" required type="text" value="<?= shop_h($form['full_name']) ?>"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-xs" for="email">Email</label>
                        <input class="w-full rounded-xl border-outline-variant" id="email" maxlength="255" name="email" required type="email" value="<?= shop_h($form['email']) ?>"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-xs" for="phone">Phone</label>
                        <input class="w-full rounded-xl border-outline-variant" id="phone" maxlength="30" name="phone" required type="text" value="<?= shop_h($form['phone']) ?>"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-xs" for="country">Country</label>
                        <input class="w-full rounded-xl border-outline-variant" id="country" maxlength="100" name="country" type="text" value="<?= shop_h($form['country']) ?>"/>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold mb-xs" for="address_line1">Address Line 1</label>
                        <input class="w-full rounded-xl border-outline-variant" id="address_line1" maxlength="255" name="address_line1" required type="text" value="<?= shop_h($form['address_line1']) ?>"/>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold mb-xs" for="address_line2">Address Line 2 (optional)</label>
                        <input class="w-full rounded-xl border-outline-variant" id="address_line2" maxlength="255" name="address_line2" type="text" value="<?= shop_h($form['address_line2']) ?>"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-xs" for="city">City</label>
                        <input class="w-full rounded-xl border-outline-variant" id="city" maxlength="100" name="city" required type="text" value="<?= shop_h($form['city']) ?>"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-xs" for="township">Township</label>
                        <input class="w-full rounded-xl border-outline-variant" id="township" maxlength="100" name="township" type="text" value="<?= shop_h($form['township']) ?>"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-xs" for="state">State / Region</label>
                        <input class="w-full rounded-xl border-outline-variant" id="state" maxlength="100" name="state" type="text" value="<?= shop_h($form['state']) ?>"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-xs" for="postal_code">Postal Code</label>
                        <input class="w-full rounded-xl border-outline-variant" id="postal_code" maxlength="20" name="postal_code" type="text" value="<?= shop_h($form['postal_code']) ?>"/>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                    <div>
                        <label class="block text-sm font-semibold mb-xs" for="payment_method">Payment</label>
                        <select class="w-full rounded-xl border-outline-variant" id="payment_method" name="payment_method">
                            <?php foreach ($paymentMethods as $methodKey => $methodLabel): ?>
                                <option value="<?= shop_h((string)$methodKey) ?>" <?= $form['payment_method'] === (string)$methodKey ? 'selected' : '' ?>><?= shop_h((string)$methodLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-xs" for="shipping_method">Shipment</label>
                        <select class="w-full rounded-xl border-outline-variant" id="shipping_method" name="shipping_method">
                            <?php foreach ($shippingMethods as $methodKey => $method): ?>
                                <?php
                                    $label = trim((string)($method['label'] ?? ucfirst((string)$methodKey)));
                                    $eta = trim((string)($method['eta'] ?? ''));
                                    $fee = (float)($method['fee'] ?? 0);
                                ?>
                                <option value="<?= shop_h((string)$methodKey) ?>" <?= $form['shipping_method'] === (string)$methodKey ? 'selected' : '' ?>>
                                    <?= shop_h($label . ' - ' . shop_money($fee) . ($eta !== '' ? ' (' . $eta . ')' : '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-xs" for="customer_note">Note (optional)</label>
                    <textarea class="w-full rounded-xl border-outline-variant" id="customer_note" maxlength="2000" name="customer_note" rows="3"><?= shop_h($form['customer_note']) ?></textarea>
                </div>

                <div class="pt-sm flex flex-wrap gap-sm">
                    <button class="px-xl py-md rounded-full bg-primary text-on-primary font-semibold hover:opacity-90" type="submit">Place Order</button>
                    <a class="px-xl py-md rounded-full border border-outline-variant text-on-surface" href="cart.php">Back to Cart</a>
                </div>
            </form>

            <aside class="lg:col-span-4">
                <div class="bg-surface-container-lowest rounded-2xl p-lg soft-shadow border border-outline-variant/20 sticky top-24">
                    <h2 class="text-headline-md font-headline-md text-primary mb-md">Order Summary</h2>
                    <div class="space-y-sm max-h-80 overflow-auto pr-1">
                        <?php foreach ($cart['items'] as $item): ?>
                            <div class="flex items-center justify-between gap-sm text-sm">
                                <div class="min-w-0">
                                    <p class="font-semibold truncate"><?= shop_h((string)$item['product_name']) ?></p>
                                    <p class="text-xs text-on-surface-variant">Qty <?= (int)$item['quantity'] ?></p>
                                </div>
                                <span class="font-semibold whitespace-nowrap"><?= shop_h(shop_money((float)$item['line_total'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-md pt-md border-t border-outline-variant/30 space-y-xs text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-on-surface-variant">Subtotal</span>
                            <span class="font-semibold"><?= shop_h(shop_money((float)$cart['subtotal'])) ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-on-surface-variant">Shipping</span>
                            <span class="font-semibold"><?= shop_h(shop_money($shippingFeePreview)) ?></span>
                        </div>
                    </div>
                    <div class="mt-md pt-md border-t border-outline-variant/30 flex items-center justify-between">
                        <span class="font-semibold">Estimated Total</span>
                        <span class="text-xl font-bold"><?= shop_h(shop_money($estimatedTotal)) ?></span>
                    </div>
                </div>
            </aside>
        </section>
    <?php endif; ?>
</main>

</body>
</html>
