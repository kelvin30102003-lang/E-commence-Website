<?php

declare(strict_types=1);

$activePage = 'track';

require_once __DIR__ . '/includes/shop_backend.php';
shop_start_session();

$orders = [];
$selectedOrder = null;
$errorMessage = '';

try {
    $pdo = shop_db();
    shop_ensure_checkout_tables($pdo);
    if (shop_user_is_logged_in()) {
        $orders = shop_fetch_customer_orders($pdo);
        $requestedOrderId = max(0, (int)($_GET['order_id'] ?? 0));
        if ($requestedOrderId > 0) {
            $selectedOrder = shop_fetch_customer_order($pdo, $requestedOrderId);
        }
        if (!is_array($selectedOrder) && count($orders) > 0) {
            $selectedOrder = $orders[0];
        }
    }
} catch (Throwable $exception) {
    $errorMessage = $exception->getMessage();
}

$steps = shop_delivery_steps();
$currentStepIndex = is_array($selectedOrder) ? shop_delivery_step_index($selectedOrder) : 0;

function track_status_label(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}

function track_badge_class(string $status): string
{
    return match ($status) {
        'paid', 'approved', 'confirmed', 'delivered' => 'bg-secondary-container text-on-secondary-container',
        'payment_pending_review', 'pending', 'preparing', 'processing', 'shipped', 'in_transit' => 'bg-tertiary-container text-on-tertiary-container',
        'failed', 'rejected', 'cancelled', 'returned' => 'bg-error-container text-on-error-container',
        default => 'bg-surface-container text-on-surface-variant',
    };
}

function track_date(?string $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '-';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    return date('M j, Y g:i A', $timestamp);
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Track Delivery | LuvShop</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&amp;family=Plus+Jakarta+Sans:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .soft-shadow { box-shadow: 0 10px 30px -5px rgba(120, 85, 94, 0.08); }
    </style>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "secondary-container": "#b2f2bb",
                        "background": "#fbf9f8",
                        "on-secondary-container": "#145129",
                        "outline": "#817476",
                        "on-surface": "#1b1c1c",
                        "tertiary-container": "#e9ddab",
                        "surface-container-lowest": "#ffffff",
                        "surface-container-low": "#f5f3f3",
                        "surface-container": "#efeded",
                        "surface": "#fbf9f8",
                        "outline-variant": "#d3c3c5",
                        "on-error-container": "#93000a",
                        "error-container": "#ffdad6",
                        "on-tertiary-container": "#696139",
                        "on-surface-variant": "#4f4446",
                        "primary-container": "#ffd1dc",
                        "primary": "#78555e",
                        "on-primary": "#ffffff"
                    },
                    borderRadius: { DEFAULT: "1rem", lg: "2rem", xl: "3rem", full: "9999px" },
                    spacing: { xs: "4px", sm: "8px", md: "16px", lg: "24px", xl: "48px", "margin-desktop": "80px" },
                    fontFamily: {
                        "headline-md": ["Quicksand"],
                        "headline-lg": ["Quicksand"],
                        "body-md": ["Plus Jakarta Sans"],
                        "label-md": ["Plus Jakarta Sans"]
                    },
                    fontSize: {
                        "headline-md": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                        "headline-lg": ["32px", {"lineHeight": "40px", "fontWeight": "700"}],
                        "body-md": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                        "label-md": ["14px", {"lineHeight": "20px", "fontWeight": "600"}]
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-background text-on-surface font-body-md overflow-x-hidden">
<?php require_once __DIR__ . '/../Templates/header.php'; ?>

<main class="pt-24 pb-xl px-4 md:px-margin-desktop max-w-[1280px] mx-auto min-h-screen">
    <section class="mb-lg flex flex-col gap-sm sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-headline-lg font-headline-lg text-primary">Track Delivery</h1>
            <p class="text-on-surface-variant">Check your order status and delivery progress.</p>
        </div>
        <a class="inline-flex items-center gap-xs rounded-full border border-outline-variant px-md py-sm text-sm font-semibold text-on-surface hover:bg-surface-container-low" href="shop.php">
            <span class="material-symbols-outlined text-[18px]">storefront</span>
            Shop
        </a>
    </section>

    <?php if ($errorMessage !== ''): ?>
        <section class="mb-lg rounded-xl border border-red-200 bg-error-container px-md py-sm text-sm text-on-error-container">
            <?= shop_h($errorMessage) ?>
        </section>
    <?php endif; ?>

    <?php if (!shop_user_is_logged_in()): ?>
        <section class="rounded-2xl bg-surface-container-lowest p-xl soft-shadow border border-outline-variant/20">
            <h2 class="text-headline-md font-headline-md text-primary mb-sm">Please log in first</h2>
            <p class="mb-md text-on-surface-variant">Login ဝင်ပြီးမှ ကိုယ့် order delivery ကို track လုပ်နိုင်ပါမယ်။</p>
            <a class="inline-flex rounded-full bg-primary px-lg py-sm font-semibold text-on-primary" href="<?= shop_h(shop_login_url('../Users/track.php')) ?>">Go to Login</a>
        </section>
    <?php elseif (count($orders) === 0): ?>
        <section class="rounded-2xl bg-surface-container-lowest p-xl text-center soft-shadow border border-outline-variant/20">
            <span class="material-symbols-outlined mb-sm text-5xl text-primary">local_shipping</span>
            <h2 class="text-headline-md font-headline-md text-primary mb-sm">No orders yet</h2>
            <p class="mb-md text-on-surface-variant">You do not have any delivery to track right now.</p>
            <a class="inline-flex rounded-full bg-primary px-lg py-sm font-semibold text-on-primary" href="shop.php">Start Shopping</a>
        </section>
    <?php else: ?>
        <section class="grid grid-cols-1 gap-lg lg:grid-cols-12">
            <aside class="lg:col-span-4">
                <div class="rounded-2xl bg-surface-container-lowest p-md soft-shadow border border-outline-variant/20 lg:sticky lg:top-24">
                    <h2 class="mb-md text-headline-md font-headline-md text-primary">My Orders</h2>
                    <div class="space-y-sm">
                        <?php foreach ($orders as $order): ?>
                            <?php
                            $isActive = is_array($selectedOrder) && (int)$selectedOrder['id'] === (int)$order['id'];
                            $deliveryIndex = shop_delivery_step_index($order);
                            $deliveryLabels = array_values($steps);
                            ?>
                            <a class="block rounded-xl border p-md transition <?= $isActive ? 'border-primary bg-primary-container/45' : 'border-outline-variant/40 bg-white hover:border-primary/50' ?>" href="track.php?order_id=<?= (int)$order['id'] ?>">
                                <div class="flex items-start justify-between gap-sm">
                                    <div>
                                        <p class="font-semibold text-primary">#<?= shop_h((string)$order['order_number']) ?></p>
                                        <p class="text-xs text-on-surface-variant"><?= shop_h(track_date((string)$order['order_datetime'])) ?></p>
                                    </div>
                                    <span class="rounded-full px-sm py-xs text-xs font-semibold <?= shop_h(track_badge_class((string)$order['shipping_status'])) ?>">
                                        <?= shop_h(track_status_label((string)$order['shipping_status'])) ?>
                                    </span>
                                </div>
                                <p class="mt-sm text-sm text-on-surface-variant"><?= shop_h((string)($deliveryLabels[$deliveryIndex]['label'] ?? 'Order Placed')) ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>

            <div class="lg:col-span-8 space-y-lg">
                <?php if (is_array($selectedOrder)): ?>
                    <section class="rounded-2xl bg-surface-container-lowest p-lg soft-shadow border border-outline-variant/20">
                        <div class="mb-lg flex flex-col gap-md sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-on-surface-variant">Order Number</p>
                                <h2 class="text-headline-md font-headline-md text-primary">#<?= shop_h((string)$selectedOrder['order_number']) ?></h2>
                                <p class="text-sm text-on-surface-variant"><?= shop_h(track_date((string)$selectedOrder['order_datetime'])) ?></p>
                            </div>
                            <div class="flex flex-wrap gap-sm">
                                <span class="rounded-full px-md py-sm text-sm font-semibold <?= shop_h(track_badge_class((string)$selectedOrder['order_status'])) ?>">
                                    <?= shop_h(track_status_label((string)$selectedOrder['order_status'])) ?>
                                </span>
                                <span class="rounded-full px-md py-sm text-sm font-semibold <?= shop_h(track_badge_class((string)$selectedOrder['payment_status'])) ?>">
                                    <?= shop_h(track_status_label((string)$selectedOrder['payment_status'])) ?>
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-sm sm:grid-cols-3">
                            <div class="rounded-xl bg-surface-container-low p-md">
                                <p class="text-xs text-on-surface-variant">Total</p>
                                <p class="font-semibold"><?= shop_h(shop_money((float)$selectedOrder['total_amount'])) ?></p>
                            </div>
                            <div class="rounded-xl bg-surface-container-low p-md">
                                <p class="text-xs text-on-surface-variant">Payment Method</p>
                                <p class="font-semibold"><?= shop_h(strtoupper((string)$selectedOrder['payment_method'])) ?></p>
                            </div>
                            <div class="rounded-xl bg-surface-container-low p-md">
                                <p class="text-xs text-on-surface-variant">Items</p>
                                <p class="font-semibold"><?= (int)$selectedOrder['item_count'] ?> item<?= (int)$selectedOrder['item_count'] === 1 ? '' : 's' ?></p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl bg-surface-container-lowest p-lg soft-shadow border border-outline-variant/20">
                        <h2 class="mb-lg text-headline-md font-headline-md text-primary">Delivery Process</h2>
                        <div class="space-y-md">
                            <?php foreach (array_values($steps) as $index => $step): ?>
                                <?php
                                $isDone = $index <= $currentStepIndex;
                                $isCurrent = $index === $currentStepIndex;
                                ?>
                                <div class="flex gap-md">
                                    <div class="flex flex-col items-center">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full border <?= $isDone ? 'border-primary bg-primary text-on-primary' : 'border-outline-variant bg-white text-on-surface-variant' ?>">
                                            <span class="material-symbols-outlined text-[20px]"><?= $isDone ? 'check' : 'radio_button_unchecked' ?></span>
                                        </div>
                                        <?php if ($index < count($steps) - 1): ?>
                                            <div class="h-10 w-[2px] <?= $index < $currentStepIndex ? 'bg-primary' : 'bg-outline-variant/50' ?>"></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="pb-md">
                                        <p class="font-semibold <?= $isCurrent ? 'text-primary' : 'text-on-surface' ?>"><?= shop_h((string)$step['label']) ?></p>
                                        <p class="text-sm text-on-surface-variant"><?= shop_h((string)$step['description']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="rounded-2xl bg-surface-container-lowest p-lg soft-shadow border border-outline-variant/20">
                        <h2 class="mb-md text-headline-md font-headline-md text-primary">Delivery Details</h2>
                        <div class="grid grid-cols-1 gap-sm sm:grid-cols-2">
                            <div class="rounded-xl bg-surface-container-low p-md">
                                <p class="text-xs text-on-surface-variant">Courier</p>
                                <p class="font-semibold"><?= shop_h((string)($selectedOrder['courier_name'] ?: 'Not assigned yet')) ?></p>
                            </div>
                            <div class="rounded-xl bg-surface-container-low p-md">
                                <p class="text-xs text-on-surface-variant">Tracking Number</p>
                                <p class="font-semibold"><?= shop_h((string)($selectedOrder['tracking_number'] ?: 'Not available yet')) ?></p>
                            </div>
                            <div class="rounded-xl bg-surface-container-low p-md">
                                <p class="text-xs text-on-surface-variant">Shipped At</p>
                                <p class="font-semibold"><?= shop_h(track_date((string)($selectedOrder['shipped_at'] ?? ''))) ?></p>
                            </div>
                            <div class="rounded-xl bg-surface-container-low p-md">
                                <p class="text-xs text-on-surface-variant">Delivered At</p>
                                <p class="font-semibold"><?= shop_h(track_date((string)($selectedOrder['delivered_at'] ?? ''))) ?></p>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../Templates/footer.php'; ?>
</body>
</html>
