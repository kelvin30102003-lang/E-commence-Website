<?php

declare(strict_types=1);

$activePage = 'shop';

require_once __DIR__ . '/includes/shop_backend.php';
shop_start_session();

$isDrawer = isset($_GET['drawer']) && (string)$_GET['drawer'] === '1';
shop_require_checkout_login($isDrawer ? '../Users/Home.php?open_cart=checkout' : '../Users/payment.php');

$dbError = null;
$paymentError = null;
$successMessage = null;
$order = null;
$paymentMethods = [];
$slips = [];
$orderId = max(0, (int)($_GET['order_id'] ?? $_POST['order_id'] ?? 0));

try {
    if ($orderId <= 0) {
        throw new InvalidArgumentException('Order ID is missing.');
    }

    $pdo = shop_db();
    shop_ensure_checkout_tables($pdo);
    $order = shop_fetch_payment_order($pdo, $orderId);
    $paymentMethods = shop_payment_method_records($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            shop_store_payment_slip($pdo, $order, $_POST, $_FILES['slip_image'] ?? []);
            header('Location: payment.php?order_id=' . $orderId . ($isDrawer ? '&drawer=1' : '') . '&uploaded=1');
            exit;
        } catch (Throwable $exception) {
            $paymentError = $exception->getMessage();
        }
    }

    if ((string)($_GET['uploaded'] ?? '') === '1') {
        $successMessage = 'Payment slip uploaded. Please wait for admin review.';
    }

    $order = shop_fetch_payment_order($pdo, $orderId);
    $slips = shop_fetch_order_payment_slips($pdo, $orderId);
} catch (Throwable $exception) {
    $dbError = $exception->getMessage();
}

$selectedMethodId = (int)($_POST['payment_method_id'] ?? ($paymentMethods[0]['id'] ?? 0));

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Payment | LuvShop</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&amp;family=Plus+Jakarta+Sans:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <style>
        body { font-family: "Plus Jakarta Sans", sans-serif; }
        .soft-shadow { box-shadow: 0 10px 30px -5px rgba(120, 85, 94, 0.08); }
        .method-card.is-active { border-color: #78555e; background: #fbf9f8; }
    </style>
</head>
<body class="bg-[#fbf9f8] text-[#1b1c1c] overflow-x-hidden">
<main class="<?= $isDrawer ? 'min-h-screen px-6 py-8' : 'min-h-screen pb-12 pt-12 px-4 md:px-20 max-w-[1280px] mx-auto' ?>" id="app-main">
    <?php if ($dbError !== null): ?>
        <section class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-800">
            <?= shop_h($dbError) ?>
        </section>
    <?php elseif (is_array($order)): ?>
        <?php $hasPendingSlip = count(array_filter($slips, static fn (array $slip): bool => (string)$slip['status'] === 'pending')) > 0; ?>

        <section class="mb-6">
            <h1 class="font-bold text-[30px] leading-9 text-[#78555e]">Complete Payment</h1>
            <p class="text-[#4f4446]">Transfer manually, then upload your payment slip for admin review.</p>
        </section>

        <?php if ($successMessage !== null): ?>
            <section class="mb-5 rounded-2xl border border-green-200 bg-[#b2f2bb] px-5 py-4 text-[#145129]">
                <?= shop_h($successMessage) ?>
            </section>
        <?php endif; ?>

        <?php if ($paymentError !== null): ?>
            <section class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-800">
                <?= shop_h($paymentError) ?>
            </section>
        <?php endif; ?>

        <section class="grid grid-cols-1 <?= $isDrawer ? '' : 'lg:grid-cols-12' ?> gap-6">
            <div class="<?= $isDrawer ? '' : 'lg:col-span-7' ?> space-y-6">
                <section class="rounded-2xl bg-white p-6 soft-shadow border border-[#d3c3c5]/40">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="rounded-xl bg-[#f5f3f3] p-4">
                            <p class="text-xs text-[#4f4446]">Order</p>
                            <p class="font-bold">#<?= shop_h((string)$order['order_number']) ?></p>
                        </div>
                        <div class="rounded-xl bg-[#f5f3f3] p-4">
                            <p class="text-xs text-[#4f4446]">Total Amount</p>
                            <p class="font-bold"><?= shop_h(shop_money((float)$order['total_amount'])) ?></p>
                        </div>
                        <div class="rounded-xl bg-[#f5f3f3] p-4">
                            <p class="text-xs text-[#4f4446]">Payment Status</p>
                            <p class="font-bold"><?= shop_h(ucfirst(str_replace('_', ' ', (string)$order['payment_status']))) ?></p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-6 soft-shadow border border-[#d3c3c5]/40">
                    <h2 class="text-xl font-bold text-[#78555e] mb-4">Choose Payment Method</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <?php foreach ($paymentMethods as $method): ?>
                            <?php $isActive = (int)$method['id'] === $selectedMethodId; ?>
                            <button class="method-card text-left rounded-xl border <?= $isActive ? 'is-active' : 'border-[#d3c3c5]/70' ?> p-4 hover:border-[#78555e]" data-method-card="<?= (int)$method['id'] ?>" type="button">
                                <span class="block font-bold"><?= shop_h((string)$method['name']) ?></span>
                                <span class="block text-sm text-[#4f4446]"><?= shop_h((string)$method['account_name']) ?></span>
                                <span class="block text-xs text-[#817476]"><?= shop_h((string)($method['account_phone'] ?: $method['account_number'] ?: '')) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <?php foreach ($paymentMethods as $method): ?>
                        <?php $isActive = (int)$method['id'] === $selectedMethodId; ?>
                        <div class="method-detail mt-5 rounded-2xl bg-[#f5f3f3] p-4" data-method-detail="<?= (int)$method['id'] ?>" <?= $isActive ? '' : 'hidden' ?>>
                            <div class="grid grid-cols-1 sm:grid-cols-[180px_1fr] gap-4">
                                <div class="rounded-xl bg-white p-3 border border-[#d3c3c5]/50">
                                    <?php if ((string)$method['qr_image'] !== '' && is_file(__DIR__ . '/' . str_replace('../Users/', '', (string)$method['qr_image']))): ?>
                                        <img class="aspect-square w-full object-contain rounded-lg" src="<?= shop_h((string)$method['qr_image']) ?>" alt="<?= shop_h((string)$method['name']) ?> QR"/>
                                    <?php else: ?>
                                        <div class="aspect-square rounded-lg border border-dashed border-[#d3c3c5] flex items-center justify-center text-center text-sm text-[#817476]">
                                            Add QR image at<br><?= shop_h((string)$method['qr_image']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="space-y-2 text-sm">
                                    <p><span class="font-semibold">Account name:</span> <?= shop_h((string)$method['account_name']) ?></p>
                                    <?php if ((string)$method['account_phone'] !== ''): ?>
                                        <p><span class="font-semibold">Phone:</span> <?= shop_h((string)$method['account_phone']) ?></p>
                                    <?php endif; ?>
                                    <?php if ((string)$method['account_number'] !== ''): ?>
                                        <p><span class="font-semibold">Account number:</span> <?= shop_h((string)$method['account_number']) ?></p>
                                    <?php endif; ?>
                                    <?php if ((string)$method['instructions'] !== ''): ?>
                                        <p class="rounded-xl bg-white p-3 text-[#4f4446]"><?= shop_h((string)$method['instructions']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </section>
            </div>

            <aside class="<?= $isDrawer ? '' : 'lg:col-span-5' ?> space-y-6">
                <section class="rounded-2xl bg-white p-6 soft-shadow border border-[#d3c3c5]/40">
                    <h2 class="text-xl font-bold text-[#78555e] mb-4">Upload Slip</h2>

                    <?php if ((string)$order['payment_status'] === 'paid'): ?>
                        <p class="rounded-xl bg-[#b2f2bb] p-4 text-[#145129]">This order is already paid.</p>
                    <?php elseif ($hasPendingSlip): ?>
                        <p class="rounded-xl bg-yellow-50 p-4 text-yellow-800">Your slip is waiting for admin review. Do not upload again unless admin rejects it.</p>
                    <?php else: ?>
                        <form class="space-y-4" enctype="multipart/form-data" method="post">
                            <input name="order_id" type="hidden" value="<?= (int)$order['id'] ?>"/>
                            <div>
                                <label class="block text-sm font-semibold mb-1" for="payment_method_id">Payment method</label>
                                <select class="w-full rounded-xl border-[#d3c3c5]" id="payment_method_id" name="payment_method_id" required>
                                    <?php foreach ($paymentMethods as $method): ?>
                                        <option value="<?= (int)$method['id'] ?>" <?= (int)$method['id'] === $selectedMethodId ? 'selected' : '' ?>><?= shop_h((string)$method['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-1" for="amount">Amount</label>
                                <input class="w-full rounded-xl border-[#d3c3c5]" id="amount" min="1" name="amount" required step="0.01" type="number" value="<?= shop_h((string)($_POST['amount'] ?? $order['total_amount'])) ?>"/>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-semibold mb-1" for="sender_name">Sender name</label>
                                    <input class="w-full rounded-xl border-[#d3c3c5]" id="sender_name" name="sender_name" type="text" value="<?= shop_h((string)($_POST['sender_name'] ?? '')) ?>"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-1" for="sender_phone">Sender phone</label>
                                    <input class="w-full rounded-xl border-[#d3c3c5]" id="sender_phone" name="sender_phone" type="text" value="<?= shop_h((string)($_POST['sender_phone'] ?? '')) ?>"/>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-1" for="transaction_id">Transaction ID</label>
                                <input class="w-full rounded-xl border-[#d3c3c5]" id="transaction_id" name="transaction_id" type="text" value="<?= shop_h((string)($_POST['transaction_id'] ?? '')) ?>"/>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-1" for="transferred_at">Transferred at</label>
                                <input class="w-full rounded-xl border-[#d3c3c5]" id="transferred_at" name="transferred_at" type="datetime-local" value="<?= shop_h((string)($_POST['transferred_at'] ?? '')) ?>"/>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-1" for="slip_image">Slip image</label>
                                <input class="w-full rounded-xl border border-[#d3c3c5] p-2" id="slip_image" name="slip_image" required type="file" accept="image/jpeg,image/png,image/webp"/>
                                <p class="mt-1 text-xs text-[#817476]">JPG, PNG, WebP. Max 4MB.</p>
                            </div>
                            <button class="w-full rounded-full bg-[#78555e] py-3 font-bold text-white hover:opacity-90" type="submit">Submit for Review</button>
                        </form>
                    <?php endif; ?>
                </section>

                <section class="rounded-2xl bg-white p-6 soft-shadow border border-[#d3c3c5]/40">
                    <h2 class="text-xl font-bold text-[#78555e] mb-4">Uploaded Slips</h2>
                    <?php if (count($slips) === 0): ?>
                        <p class="text-sm text-[#817476]">No slips uploaded yet.</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($slips as $slip): ?>
                                <?php
                                    $status = (string)$slip['status'];
                                    $badgeClass = match ($status) {
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        default => 'bg-yellow-100 text-yellow-800',
                                    };
                                ?>
                                <div class="rounded-xl border border-[#d3c3c5]/50 p-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="font-semibold"><?= shop_h((string)$slip['payment_method_name']) ?></p>
                                            <p class="text-sm text-[#4f4446]"><?= shop_h(shop_money((float)$slip['amount'])) ?></p>
                                        </div>
                                        <span class="rounded-full px-3 py-1 text-xs font-bold <?= $badgeClass ?>"><?= shop_h(ucfirst($status)) ?></span>
                                    </div>
                                    <?php if ((string)$slip['reject_reason'] !== ''): ?>
                                        <p class="mt-2 text-sm text-red-700">Reject reason: <?= shop_h((string)$slip['reject_reason']) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </aside>
        </section>
    <?php endif; ?>
</main>

<script>
(() => {
    const select = document.getElementById('payment_method_id');
    const activate = (id) => {
        document.querySelectorAll('[data-method-card]').forEach((card) => {
            card.classList.toggle('is-active', card.dataset.methodCard === id);
        });
        document.querySelectorAll('[data-method-detail]').forEach((detail) => {
            detail.hidden = detail.dataset.methodDetail !== id;
        });
        if (select) {
            select.value = id;
        }
    };

    document.querySelectorAll('[data-method-card]').forEach((card) => {
        card.addEventListener('click', () => activate(card.dataset.methodCard || ''));
    });

    if (select) {
        select.addEventListener('change', () => activate(select.value));
    }
})();
</script>
</body>
</html>
