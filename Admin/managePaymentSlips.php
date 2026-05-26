<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_layout.php';
require_once __DIR__ . '/../Users/includes/shop_backend.php';

admin_start_session();
$admin = admin_require_auth('adminLogin.php');
$pdo = admin_db();
shop_ensure_checkout_tables($pdo);
shop_ensure_payment_tables($pdo);

$csrfToken = admin_bootstrap_csrf_token();
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!admin_validate_csrf_token((string)($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Invalid form token. Please refresh and try again.');
        }

        $action = (string)($_POST['action'] ?? '');
        $slipId = max(0, (int)($_POST['slip_id'] ?? 0));
        if ($slipId <= 0) {
            throw new InvalidArgumentException('Payment slip ID is missing.');
        }

        if ($action === 'approve') {
            approvePaymentSlip($pdo, $slipId, (int)$admin['id'], trim((string)($_POST['admin_note'] ?? '')));
            $flash = ['type' => 'success', 'message' => 'Payment approved. Order is now paid.'];
        } elseif ($action === 'reject') {
            $reason = trim((string)($_POST['reject_reason'] ?? ''));
            if ($reason === '') {
                throw new InvalidArgumentException('Reject reason is required.');
            }
            rejectPaymentSlip($pdo, $slipId, (int)$admin['id'], $reason, trim((string)($_POST['admin_note'] ?? '')));
            $flash = ['type' => 'success', 'message' => 'Payment slip rejected. Customer can upload again.'];
        }
    } catch (Throwable $exception) {
        $flash = ['type' => 'error', 'message' => $exception->getMessage()];
    }
}

$status = trim((string)($_GET['status'] ?? 'pending'));
$methodId = max(0, (int)($_GET['payment_method_id'] ?? 0));
$date = trim((string)($_GET['date'] ?? ''));
$viewId = max(0, (int)($_GET['view_id'] ?? 0));
$paymentMethods = shop_payment_method_records($pdo);
$slips = fetchPaymentSlips($pdo, $status, $methodId, $date);
$viewSlip = $viewId > 0 ? fetchPaymentSlipById($pdo, $viewId) : null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Payment Slips | LuvShop Admin</title>
    <?php admin_render_critical_css(); ?>
    <?php $adminCssHref = admin_css_href(); ?>
    <?php if ($adminCssHref !== null): ?>
        <link href="<?= admin_html($adminCssHref) ?>" rel="stylesheet"/>
    <?php endif; ?>
    <link href="<?= admin_html(admin_material_symbols_href()) ?>" rel="stylesheet"/>
</head>
<body>
<?php admin_render_sidebar($admin, 'payment_slips'); ?>
<main class="ml-64 min-h-screen bg-surface" id="app-main" data-payment-slip-page data-payment-slip-status="<?= admin_html($status) ?>" data-payment-slip-method-id="<?= (int)$methodId ?>" data-payment-slip-date="<?= admin_html($date) ?>" data-payment-slip-total="<?= count($slips) ?>" data-payment-slip-latest="<?= count($slips) > 0 ? (int)max(array_map(static fn(array $slip): int => (int)$slip['id'], $slips)) : 0 ?>">
    <?php admin_render_header($admin, [
        'search_enabled' => false,
    ]); ?>

    <div class="px-lg py-xl max-w-[1280px] mx-auto">
        <div class="mb-lg flex items-start justify-between gap-md">
            <div>
                <h1 class="text-3xl font-bold text-[#78555e]">Payment Slip Reviews</h1>
                <p class="text-on-surface-variant">Approve only after checking your real wallet or bank app.</p>
            </div>
        </div>

        <?php if ($flash !== null): ?>
            <div class="mb-lg rounded-lg px-lg py-md border <?= $flash['type'] === 'error' ? 'bg-error-container border-red-200 text-on-error-container' : 'bg-secondary-container border-green-200 text-on-secondary-container' ?>">
                <?= admin_html($flash['message']) ?>
            </div>
        <?php endif; ?>

        <form class="mb-lg grid grid-cols-1 md:grid-cols-4 gap-md bg-white border border-outline-variant/30 rounded-xl p-lg" method="get">
            <select name="status">
                <option value="">All statuses</option>
                <?php foreach (['pending', 'approved', 'rejected'] as $option): ?>
                    <option value="<?= admin_html($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= admin_html(ucfirst($option)) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="payment_method_id">
                <option value="0">All methods</option>
                <?php foreach ($paymentMethods as $method): ?>
                    <option value="<?= (int)$method['id'] ?>" <?= $methodId === (int)$method['id'] ? 'selected' : '' ?>><?= admin_html((string)$method['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input name="date" type="date" value="<?= admin_html($date) ?>"/>
            <button class="rounded-xl bg-[#78555e] px-5 py-3 text-white font-semibold" type="submit">Filter</button>
        </form>

        <?php if (is_array($viewSlip)): ?>
            <section class="mb-lg grid grid-cols-1 lg:grid-cols-12 gap-lg">
                <div class="lg:col-span-7 bg-white rounded-xl border border-outline-variant/30 p-lg">
                    <h2 class="text-xl font-bold text-[#78555e] mb-md">Slip Image</h2>
                    <img class="w-full max-h-[720px] object-contain rounded-xl bg-slate-50" src="<?= admin_html((string)$viewSlip['slip_image']) ?>" alt="Payment slip"/>
                </div>
                <aside class="lg:col-span-5 space-y-md">
                    <div class="bg-white rounded-xl border border-outline-variant/30 p-lg">
                        <h2 class="text-xl font-bold text-[#78555e] mb-md">Review Details</h2>
                        <dl class="space-y-sm text-sm">
                            <div><dt class="text-slate-500">Order</dt><dd class="font-semibold">#<?= admin_html((string)$viewSlip['order_number']) ?></dd></div>
                            <div><dt class="text-slate-500">Customer</dt><dd class="font-semibold"><?= admin_html((string)$viewSlip['customer_name']) ?></dd><dd><?= admin_html((string)$viewSlip['customer_email']) ?></dd></div>
                            <div><dt class="text-slate-500">Order Total</dt><dd class="font-semibold"><?= admin_html(admin_format_money((float)$viewSlip['total_amount'])) ?></dd></div>
                            <div><dt class="text-slate-500">Uploaded Amount</dt><dd class="font-semibold"><?= admin_html(admin_format_money((float)$viewSlip['amount'])) ?></dd></div>
                            <div><dt class="text-slate-500">Method</dt><dd class="font-semibold"><?= admin_html((string)$viewSlip['payment_method_name']) ?></dd></div>
                            <div><dt class="text-slate-500">Transaction ID</dt><dd class="font-semibold"><?= admin_html((string)($viewSlip['transaction_id'] ?: '-')) ?></dd></div>
                            <div><dt class="text-slate-500">Transferred At</dt><dd class="font-semibold"><?= admin_html((string)($viewSlip['transferred_at'] ?: '-')) ?></dd></div>
                            <div><dt class="text-slate-500">Status</dt><dd class="font-semibold"><?= admin_html(ucfirst((string)$viewSlip['status'])) ?></dd></div>
                        </dl>
                    </div>

                    <?php if ((string)$viewSlip['status'] === 'pending'): ?>
                        <form class="bg-white rounded-xl border border-outline-variant/30 p-lg space-y-md" method="post">
                            <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
                            <input name="slip_id" type="hidden" value="<?= (int)$viewSlip['id'] ?>"/>
                            <input name="action" type="hidden" value="approve"/>
                            <textarea name="admin_note" rows="3" placeholder="Admin note (optional)"></textarea>
                            <button class="w-full rounded-xl bg-green-600 px-5 py-3 text-white font-semibold" type="submit">Approve Payment</button>
                        </form>

                        <form class="bg-white rounded-xl border border-outline-variant/30 p-lg space-y-md" method="post">
                            <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
                            <input name="slip_id" type="hidden" value="<?= (int)$viewSlip['id'] ?>"/>
                            <input name="action" type="hidden" value="reject"/>
                            <textarea name="reject_reason" rows="3" placeholder="Reject reason" required></textarea>
                            <textarea name="admin_note" rows="2" placeholder="Admin note (optional)"></textarea>
                            <button class="w-full rounded-xl bg-red-600 px-5 py-3 text-white font-semibold" type="submit">Reject Slip</button>
                        </form>
                    <?php endif; ?>
                </aside>
            </section>
        <?php endif; ?>

        <section class="bg-white rounded-xl border border-outline-variant/30 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-sm text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Order</th>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Method</th>
                        <th class="px-5 py-3">Amount</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (count($slips) === 0): ?>
                        <tr><td class="px-5 py-6 text-slate-500" colspan="6">No payment slips found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($slips as $slip): ?>
                        <tr>
                            <td class="px-5 py-4 font-semibold">#<?= admin_html((string)$slip['order_number']) ?></td>
                            <td class="px-5 py-4">
                                <p><?= admin_html((string)$slip['customer_name']) ?></p>
                                <p class="text-xs text-slate-500"><?= admin_html((string)$slip['customer_email']) ?></p>
                            </td>
                            <td class="px-5 py-4"><?= admin_html((string)$slip['payment_method_name']) ?></td>
                            <td class="px-5 py-4 font-semibold"><?= admin_html(admin_format_money((float)$slip['amount'])) ?></td>
                            <td class="px-5 py-4"><?= admin_html(ucfirst((string)$slip['status'])) ?></td>
                            <td class="px-5 py-4 text-right">
                                <a class="text-[#78555e] font-semibold hover:underline" href="managePaymentSlips.php?view_id=<?= (int)$slip['id'] ?>&status=<?= urlencode($status) ?>&payment_method_id=<?= (int)$methodId ?>&date=<?= urlencode($date) ?>">Review</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
</main>
<script>
(() => {
    const page = document.querySelector('[data-payment-slip-page]');
    if (!page || page.dataset.pollStarted === 'true') {
        return;
    }
    page.dataset.pollStarted = 'true';

    const initialLatest = parseInt(page.dataset.paymentSlipLatest || '0', 10) || 0;
    const initialTotal = parseInt(page.dataset.paymentSlipTotal || '0', 10) || 0;
    let currentLatest = initialLatest;
    let currentTotal = initialTotal;
    let inFlight = false;

    const poll = async () => {
        if (document.hidden || inFlight) {
            return;
        }
        inFlight = true;
        try {
            const statusUrl = new URL('paymentSlipStatus.php', window.location.href);
            statusUrl.searchParams.set('status', page.dataset.paymentSlipStatus || '');
            statusUrl.searchParams.set('payment_method_id', page.dataset.paymentSlipMethodId || '0');
            statusUrl.searchParams.set('date', page.dataset.paymentSlipDate || '');

            const response = await fetch(statusUrl.toString(), {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            });
            if (!response.ok) {
                return;
            }
            const data = await response.json();
            const latest = parseInt(String(data.latest_id || 0), 10) || 0;
            const total = parseInt(String(data.total_count || 0), 10) || 0;
            if (latest > currentLatest || total !== currentTotal) {
                window.location.reload();
                return;
            }
            currentLatest = latest;
            currentTotal = total;
        } catch (_error) {
        } finally {
            inFlight = false;
        }
    };

    window.setInterval(poll, 4000);
})();
</script>
</body>
</html>

<?php

function fetchPaymentSlips(PDO $pdo, string $status, int $methodId, string $date): array
{
    $where = ['1 = 1'];
    $params = [];
    if ($status !== '') {
        $where[] = 'ps.status = :status';
        $params[':status'] = $status;
    }
    if ($methodId > 0) {
        $where[] = 'ps.payment_method_id = :payment_method_id';
        $params[':payment_method_id'] = $methodId;
    }
    if ($date !== '') {
        $where[] = 'DATE(ps.created_at) = :created_date';
        $params[':created_date'] = $date;
    }

    $statement = $pdo->prepare(
        'SELECT
            ps.*,
            COALESCE(o.order_number, CONCAT("Order ", ps.order_id)) AS order_number,
            COALESCE(o.total_amount, 0) AS total_amount,
            COALESCE(u.name, "Customer") AS customer_name,
            COALESCE(u.email, "") AS customer_email,
            COALESCE(pm.name, "Payment Method") AS payment_method_name
         FROM payment_slips ps
         LEFT JOIN orders o ON o.id = ps.order_id
         LEFT JOIN users u ON u.id = ps.user_id
         LEFT JOIN payment_methods pm ON pm.id = ps.payment_method_id
         WHERE ' . implode(' AND ', $where) . '
         ORDER BY ps.created_at DESC, ps.id DESC
         LIMIT 100'
    );
    $statement->execute($params);
    $rows = $statement->fetchAll();

    return is_array($rows) ? $rows : [];
}

function fetchPaymentSlipById(PDO $pdo, int $id): ?array
{
    $statement = $pdo->prepare(
        'SELECT
            ps.*,
            COALESCE(o.order_number, CONCAT("Order ", ps.order_id)) AS order_number,
            COALESCE(o.total_amount, 0) AS total_amount,
            COALESCE(u.name, "Customer") AS customer_name,
            COALESCE(u.email, "") AS customer_email,
            COALESCE(pm.name, "Payment Method") AS payment_method_name,
            COALESCE(pm.code, "") AS payment_method_code
         FROM payment_slips ps
         LEFT JOIN orders o ON o.id = ps.order_id
         LEFT JOIN users u ON u.id = ps.user_id
         LEFT JOIN payment_methods pm ON pm.id = ps.payment_method_id
         WHERE ps.id = :id
         LIMIT 1'
    );
    $statement->execute([':id' => $id]);
    $row = $statement->fetch();

    return is_array($row) ? $row : null;
}

function approvePaymentSlip(PDO $pdo, int $slipId, int $adminId, string $adminNote): void
{
    $pdo->beginTransaction();
    try {
        $slip = fetchPaymentSlipById($pdo, $slipId);
        if (!is_array($slip) || (string)$slip['status'] !== 'pending') {
            throw new RuntimeException('This slip was already reviewed.');
        }

        $updateSlip = $pdo->prepare(
            'UPDATE payment_slips
             SET status = :status, reviewed_by = :reviewed_by, reviewed_at = NOW(), admin_note = :admin_note, updated_at = NOW()
             WHERE id = :id AND status = :pending'
        );
        $updateSlip->execute([
            ':status' => 'approved',
            ':reviewed_by' => $adminId,
            ':admin_note' => $adminNote !== '' ? $adminNote : null,
            ':id' => $slipId,
            ':pending' => 'pending',
        ]);
        if ($updateSlip->rowCount() !== 1) {
            throw new RuntimeException('This slip was already reviewed.');
        }

        $pdo->prepare('UPDATE orders SET payment_status = "paid", order_status = "confirmed", updated_at = NOW() WHERE id = :id')
            ->execute([':id' => (int)$slip['order_id']]);

        $insertPayment = $pdo->prepare(
            'INSERT INTO payments (order_id, payment_method, payment_provider, transaction_id, amount, currency, status, paid_at, created_at, updated_at)
             VALUES (:order_id, :payment_method, :payment_provider, :transaction_id, :amount, "MMK", "paid", NOW(), NOW(), NOW())'
        );
        $insertPayment->execute([
            ':order_id' => (int)$slip['order_id'],
            ':payment_method' => (string)$slip['payment_method_code'],
            ':payment_provider' => (string)$slip['payment_method_name'],
            ':transaction_id' => $slip['transaction_id'],
            ':amount' => (float)$slip['amount'],
        ]);

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function rejectPaymentSlip(PDO $pdo, int $slipId, int $adminId, string $reason, string $adminNote): void
{
    $pdo->beginTransaction();
    try {
        $slip = fetchPaymentSlipById($pdo, $slipId);
        if (!is_array($slip) || (string)$slip['status'] !== 'pending') {
            throw new RuntimeException('This slip was already reviewed.');
        }

        $updateSlip = $pdo->prepare(
            'UPDATE payment_slips
             SET status = :status, reviewed_by = :reviewed_by, reviewed_at = NOW(), reject_reason = :reject_reason, admin_note = :admin_note, updated_at = NOW()
             WHERE id = :id AND status = :pending'
        );
        $updateSlip->execute([
            ':status' => 'rejected',
            ':reviewed_by' => $adminId,
            ':reject_reason' => $reason,
            ':admin_note' => $adminNote !== '' ? $adminNote : null,
            ':id' => $slipId,
            ':pending' => 'pending',
        ]);
        if ($updateSlip->rowCount() !== 1) {
            throw new RuntimeException('This slip was already reviewed.');
        }

        $pdo->prepare('UPDATE orders SET payment_status = "rejected", order_status = "pending", updated_at = NOW() WHERE id = :id')
            ->execute([':id' => (int)$slip['order_id']]);

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}
