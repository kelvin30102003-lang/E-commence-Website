<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_layout.php';

admin_start_session();
$admin = admin_require_auth('adminLogin.php');

$pdo = admin_db();
admin_ensure_tables($pdo);

$csrfToken = admin_bootstrap_csrf_token();
$statusOptions = ['draft', 'active', 'inactive', 'archived'];

$queryFilters = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'category_id' => (int)($_GET['category_id'] ?? 0),
    'status' => trim((string)($_GET['status'] ?? '')),
    'stock' => trim((string)($_GET['stock'] ?? '')),
    'per_page' => max(5, min(100, (int)($_GET['per_page'] ?? 10))),
    'page' => max(1, (int)($_GET['page'] ?? 1)),
];

$mode = trim((string)($_GET['mode'] ?? ''));
$editProductId = max(0, (int)($_GET['edit_id'] ?? 0));
$variantProductId = max(0, (int)($_GET['variant_product_id'] ?? 0));
$variantEditId = max(0, (int)($_GET['variant_edit_id'] ?? 0));

handleProductPostActions($pdo, $admin, $statusOptions);

$flash = pullAdminFlash();

$categories = fetchAllCategories($pdo);
$brands = fetchAllBrands($pdo);
$stats = fetchProductStats($pdo);

$listData = fetchProductList($pdo, $queryFilters);
$products = $listData['rows'];
$totalRows = $listData['total'];
$totalPages = max(1, (int)ceil($totalRows / $queryFilters['per_page']));
$currentPage = min($queryFilters['page'], $totalPages);

if ($currentPage !== $queryFilters['page']) {
    $queryFilters['page'] = $currentPage;
    $listData = fetchProductList($pdo, $queryFilters);
    $products = $listData['rows'];
}

$editingProduct = null;
if ($mode === 'edit' && $editProductId > 0) {
    $editingProduct = fetchProductById($pdo, $editProductId);
    if ($editingProduct === null) {
        setAdminFlash('error', 'Product to edit was not found.');
        header('Location: manageProducts.php');
        exit;
    }
}

$variantProduct = null;
$variants = [];
$editingVariant = null;
if ($variantProductId > 0) {
    $variantProduct = fetchProductById($pdo, $variantProductId);
    if ($variantProduct !== null) {
        $variants = fetchVariantsByProductId($pdo, $variantProductId);
        if ($variantEditId > 0) {
            $editingVariant = fetchVariantById($pdo, $variantEditId);
            if ($editingVariant !== null && (int)$editingVariant['product_id'] !== (int)$variantProduct['id']) {
                $editingVariant = null;
            }
        }
    } else {
        setAdminFlash('error', 'Product for variants was not found.');
        header('Location: manageProducts.php');
        exit;
    }
}

$baseQuery = buildFilterQuery($queryFilters);

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Manage Products - LuvShop Admin</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&amp;family=Quicksand:wght@500;600;700&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <script defer src="../Assect/js/site-ajax.js"></script>
    <style>
        body { background-color: #fbf9f8; font-family: 'Plus Jakarta Sans', sans-serif; -webkit-font-smoothing: antialiased; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .soft-shadow { box-shadow: 0 10px 30px -10px rgba(120, 85, 94, 0.08); }
    </style>
</head>
<body class="min-h-screen text-slate-900 bg-[#fbf9f8]">
<?php
admin_render_sidebar($admin, 'products');
?>

<main class="ml-64 flex-1 min-w-0" id="app-main">
    <?php
    admin_render_header($admin, [
        'search_action' => 'manageProducts.php',
        'search_method' => 'get',
        'search_name' => 'q',
        'search_value' => $queryFilters['q'],
        'search_placeholder' => 'Search products, SKUs...',
        'search_hidden' => [
            'category_id' => (int)$queryFilters['category_id'],
            'status' => $queryFilters['status'],
            'stock' => $queryFilters['stock'],
            'per_page' => (int)$queryFilters['per_page'],
        ],
    ]);
    ?>

    <section class="p-6 max-w-[1280px] w-full mx-auto">
        <div class="flex items-end justify-between mb-8">
            <div>
                <h2 class="font-semibold text-3xl text-[#78555e] mb-1">Products</h2>
                <p class="text-slate-500">Manage your inventory, variants, and stock levels.</p>
            </div>
            <div class="flex items-center gap-2">
                <a class="bg-[#78555e] text-white px-5 py-3 rounded-full flex items-center gap-2 shadow-lg hover:opacity-90" href="manageProducts.php<?= admin_html($baseQuery) ?>&mode=create"><span class="material-symbols-outlined">add</span>Create Product</a>
            </div>
        </div>

        <?php if ($flash !== null): ?>
            <div class="mb-6 rounded-lg px-4 py-3 text-sm <?= $flash['type'] === 'error' ? 'bg-red-100 text-red-800 border border-red-200' : 'bg-green-100 text-green-800 border border-green-200' ?>">
                <?= admin_html($flash['message']) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl soft-shadow">
                <p class="text-sm text-[#78555e] font-semibold">Total Items</p>
                <p class="text-3xl font-bold mt-1"><?= number_format((int)$stats['total_items']) ?></p>
            </div>
            <div class="bg-white p-6 rounded-2xl soft-shadow border-l-4 border-red-300">
                <p class="text-sm text-red-600 font-semibold">Low Stock Alerts</p>
                <p class="text-3xl font-bold mt-1"><?= number_format((int)$stats['low_stock']) ?></p>
            </div>
            <div class="bg-white p-6 rounded-2xl soft-shadow">
                <p class="text-sm text-slate-600 font-semibold">Active Listings</p>
                <p class="text-3xl font-bold mt-1"><?= number_format((int)$stats['active_items']) ?></p>
            </div>
            <div class="bg-white p-6 rounded-2xl soft-shadow">
                <p class="text-sm text-slate-600 font-semibold">Draft Items</p>
                <p class="text-3xl font-bold mt-1"><?= number_format((int)$stats['draft_items']) ?></p>
            </div>
        </div>

        <?php if (count($categories) === 0 || count($brands) === 0): ?>
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 mb-8">
                <p class="text-sm text-amber-800 font-semibold mb-3">Category/Brand setup</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php if (count($categories) === 0): ?>
                        <form method="post" class="flex items-end gap-2">
                            <input type="hidden" name="csrf_token" value="<?= admin_html($csrfToken) ?>" />
                            <input type="hidden" name="action" value="create_category" />
                            <input type="hidden" name="return_query" value="<?= admin_html($baseQuery) ?>" />
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-amber-800 mb-1">Create Category</label>
                                <input class="w-full rounded-xl border-amber-300" name="name" required placeholder="e.g. Plush Toys" type="text" />
                            </div>
                            <button class="rounded-xl bg-amber-600 text-white px-4 py-2 text-sm font-semibold" type="submit">Add</button>
                        </form>
                    <?php endif; ?>
                    <?php if (count($brands) === 0): ?>
                        <form method="post" class="flex items-end gap-2">
                            <input type="hidden" name="csrf_token" value="<?= admin_html($csrfToken) ?>" />
                            <input type="hidden" name="action" value="create_brand" />
                            <input type="hidden" name="return_query" value="<?= admin_html($baseQuery) ?>" />
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-amber-800 mb-1">Create Brand</label>
                                <input class="w-full rounded-xl border-amber-300" name="name" required placeholder="e.g. LuvSoft" type="text" />
                            </div>
                            <button class="rounded-xl bg-amber-600 text-white px-4 py-2 text-sm font-semibold" type="submit">Add</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($mode === 'create' || ($mode === 'edit' && $editingProduct !== null)): ?>
            <div class="bg-white rounded-2xl soft-shadow p-6 mb-8">
                <h3 class="text-xl font-semibold text-[#78555e] mb-4"><?= $mode === 'edit' ? 'Edit Product' : 'Create Product' ?></h3>
                <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="hidden" name="csrf_token" value="<?= admin_html($csrfToken) ?>" />
                    <input type="hidden" name="action" value="<?= $mode === 'edit' ? 'update_product' : 'create_product' ?>" />
                    <input type="hidden" name="return_query" value="<?= admin_html($baseQuery) ?>" />
                    <?php if ($mode === 'edit' && $editingProduct !== null): ?>
                        <input type="hidden" name="product_id" value="<?= (int)$editingProduct['id'] ?>" />
                    <?php endif; ?>

                    <div>
                        <label class="block text-sm font-semibold mb-1">Product Name</label>
                        <input class="w-full rounded-xl border-slate-300" name="name" required type="text" value="<?= admin_html((string)($editingProduct['name'] ?? '')) ?>" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Slug (optional)</label>
                        <input class="w-full rounded-xl border-slate-300" name="slug" type="text" value="<?= admin_html((string)($editingProduct['slug'] ?? '')) ?>" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Category</label>
                        <select class="w-full rounded-xl border-slate-300" name="category_id">
                            <option value="0">No Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int)$category['id'] ?>" <?= (int)($editingProduct['category_id'] ?? 0) === (int)$category['id'] ? 'selected' : '' ?>><?= admin_html((string)$category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Brand</label>
                        <select class="w-full rounded-xl border-slate-300" name="brand_id">
                            <option value="0">No Brand</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?= (int)$brand['id'] ?>" <?= (int)($editingProduct['brand_id'] ?? 0) === (int)$brand['id'] ? 'selected' : '' ?>><?= admin_html((string)$brand['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Status</label>
                        <select class="w-full rounded-xl border-slate-300" name="status">
                            <?php foreach ($statusOptions as $statusOption): ?>
                                <option value="<?= admin_html($statusOption) ?>" <?= (($editingProduct['status'] ?? 'draft') === $statusOption) ? 'selected' : '' ?>><?= admin_html(ucfirst($statusOption)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Initial SKU (optional)</label>
                        <input class="w-full rounded-xl border-slate-300" name="initial_sku" type="text" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Initial Price</label>
                        <input class="w-full rounded-xl border-slate-300" min="0" name="initial_price" step="0.01" type="number" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Initial Stock</label>
                        <input class="w-full rounded-xl border-slate-300" min="0" name="initial_stock" step="1" type="number" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold mb-1">Short Description</label>
                        <input class="w-full rounded-xl border-slate-300" maxlength="500" name="short_description" type="text" value="<?= admin_html((string)($editingProduct['short_description'] ?? '')) ?>" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold mb-1">Description</label>
                        <textarea class="w-full rounded-xl border-slate-300" name="description" rows="4"><?= admin_html((string)($editingProduct['description'] ?? '')) ?></textarea>
                    </div>
                    <div class="md:col-span-2 flex items-center gap-3">
                        <button class="bg-[#78555e] text-white px-5 py-2.5 rounded-full font-semibold" type="submit"><?= $mode === 'edit' ? 'Update Product' : 'Create Product' ?></button>
                        <a class="px-4 py-2.5 rounded-full border border-slate-300 text-slate-700" href="manageProducts.php<?= admin_html($baseQuery) ?>">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl soft-shadow overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <form class="grid grid-cols-1 md:grid-cols-6 gap-3" method="get">
                    <div class="md:col-span-2">
                        <input class="w-full rounded-full border-slate-300" name="q" placeholder="Search name, slug, SKU..." type="text" value="<?= admin_html($queryFilters['q']) ?>" />
                    </div>
                    <div>
                        <select class="w-full rounded-full border-slate-300" name="category_id">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int)$category['id'] ?>" <?= $queryFilters['category_id'] === (int)$category['id'] ? 'selected' : '' ?>><?= admin_html((string)$category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <select class="w-full rounded-full border-slate-300" name="status">
                            <option value="">All Status</option>
                            <?php foreach ($statusOptions as $statusOption): ?>
                                <option value="<?= admin_html($statusOption) ?>" <?= $queryFilters['status'] === $statusOption ? 'selected' : '' ?>><?= admin_html(ucfirst($statusOption)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <select class="w-full rounded-full border-slate-300" name="stock">
                            <option value="">All Stock</option>
                            <option value="low" <?= $queryFilters['stock'] === 'low' ? 'selected' : '' ?>>Low Stock</option>
                            <option value="out" <?= $queryFilters['stock'] === 'out' ? 'selected' : '' ?>>Out of Stock</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <input type="hidden" name="per_page" value="<?= (int)$queryFilters['per_page'] ?>" />
                        <button class="px-4 py-2 rounded-full bg-slate-900 text-white text-sm font-semibold" type="submit">Filter</button>
                        <a class="px-4 py-2 rounded-full border border-slate-300 text-sm font-semibold" href="manageProducts.php">Reset</a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-slate-50">
                            <th class="text-left py-4 px-6 text-xs uppercase tracking-wider text-slate-500">Product</th>
                            <th class="text-left py-4 px-4 text-xs uppercase tracking-wider text-slate-500">Category</th>
                            <th class="text-left py-4 px-4 text-xs uppercase tracking-wider text-slate-500">Brand</th>
                            <th class="text-left py-4 px-4 text-xs uppercase tracking-wider text-slate-500">Price</th>
                            <th class="text-left py-4 px-4 text-xs uppercase tracking-wider text-slate-500">Stock</th>
                            <th class="text-left py-4 px-4 text-xs uppercase tracking-wider text-slate-500">Status</th>
                            <th class="text-right py-4 px-6 text-xs uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (count($products) === 0): ?>
                            <tr><td class="px-6 py-8 text-slate-500" colspan="7">No products found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($products as $product): ?>
                            <?php
                                $priceLabel = $product['min_price'] === null
                                    ? '--'
                                    : (((float)$product['min_price'] === (float)$product['max_price'])
                                        ? '$' . number_format((float)$product['min_price'], 2)
                                        : '$' . number_format((float)$product['min_price'], 2) . ' - $' . number_format((float)$product['max_price'], 2));
                                $stockQty = (int)$product['stock_total'];
                                $stockPercent = max(3, min(100, (int)round(($stockQty / max(1, (int)$product['stock_target'])) * 100)));
                            ?>
                            <tr class="hover:bg-slate-50">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-lg bg-slate-100 overflow-hidden flex-shrink-0 flex items-center justify-center">
                                            <?php if ((string)$product['image'] !== ''): ?>
                                                <img alt="<?= admin_html((string)$product['name']) ?>" class="w-full h-full object-cover" src="<?= admin_html((string)$product['image']) ?>" />
                                            <?php else: ?>
                                                <span class="material-symbols-outlined text-slate-400">inventory_2</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-sm text-[#78555e] truncate"><?= admin_html((string)$product['name']) ?></p>
                                            <p class="text-xs text-slate-500">Slug: <?= admin_html((string)$product['slug']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-sm"><?= admin_html((string)($product['category_name'] ?? '--')) ?></td>
                                <td class="py-4 px-4 text-sm"><?= admin_html((string)($product['brand_name'] ?? '--')) ?></td>
                                <td class="py-4 px-4 text-sm font-semibold"><?= admin_html($priceLabel) ?></td>
                                <td class="py-4 px-4 text-sm">
                                    <div class="flex flex-col gap-1">
                                        <span class="<?= $stockQty <= 0 ? 'text-red-600 font-semibold' : 'text-slate-700' ?>"><?= number_format($stockQty) ?> units</span>
                                        <div class="w-24 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="<?= $stockQty <= 0 ? 'bg-red-500' : ($stockQty <= (int)$product['stock_target'] ? 'bg-amber-500' : 'bg-green-500') ?> h-full rounded-full" style="width: <?= $stockPercent ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-4"><span class="px-3 py-1 rounded-full text-xs font-semibold <?= productStatusBadgeClass((string)$product['status']) ?>"><?= admin_html(ucfirst((string)$product['status'])) ?></span></td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2 flex-wrap">
                                        <a class="bg-slate-100 px-3 py-1 rounded-full text-xs font-semibold hover:bg-slate-200" href="manageProducts.php<?= admin_html($baseQuery) ?>&variant_product_id=<?= (int)$product['id'] ?>">Manage Variants</a>
                                        <a class="p-2 rounded-full text-slate-600 hover:bg-slate-100" href="manageProducts.php<?= admin_html($baseQuery) ?>&mode=edit&edit_id=<?= (int)$product['id'] ?>" title="Edit"><span class="material-symbols-outlined text-[20px]">edit</span></a>

                                        <?php if ((string)$product['status'] !== 'archived'): ?>
                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?= admin_html($csrfToken) ?>" />
                                                <input type="hidden" name="action" value="archive_product" />
                                                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>" />
                                                <input type="hidden" name="return_query" value="<?= admin_html($baseQuery) ?>" />
                                                <button class="p-2 rounded-full text-slate-600 hover:bg-red-100 hover:text-red-700" title="Archive" type="submit"><span class="material-symbols-outlined text-[20px]">archive</span></button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?= admin_html($csrfToken) ?>" />
                                                <input type="hidden" name="action" value="restore_product" />
                                                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>" />
                                                <input type="hidden" name="return_query" value="<?= admin_html($baseQuery) ?>" />
                                                <button class="p-2 rounded-full text-slate-600 hover:bg-slate-100" title="Restore" type="submit"><span class="material-symbols-outlined text-[20px]">settings_backup_restore</span></button>
                                            </form>
                                            <form method="post" onsubmit="return confirm('Delete this product permanently?')">
                                                <input type="hidden" name="csrf_token" value="<?= admin_html($csrfToken) ?>" />
                                                <input type="hidden" name="action" value="delete_product" />
                                                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>" />
                                                <input type="hidden" name="return_query" value="<?= admin_html($baseQuery) ?>" />
                                                <button class="p-2 rounded-full text-red-600 hover:bg-red-100" title="Delete" type="submit"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-slate-50 flex items-center justify-between gap-4 flex-wrap">
                <div class="text-sm text-slate-500">
                    Showing <?= $totalRows === 0 ? 0 : (($currentPage - 1) * $queryFilters['per_page'] + 1) ?>-<?= min($currentPage * $queryFilters['per_page'], $totalRows) ?> of <?= number_format($totalRows) ?> products
                </div>
                <div class="flex items-center gap-2">
                    <?php $prevPage = max(1, $currentPage - 1); $nextPage = min($totalPages, $currentPage + 1); ?>
                    <a class="w-10 h-10 rounded-full border border-slate-300 flex items-center justify-center <?= $currentPage <= 1 ? 'pointer-events-none opacity-50' : 'hover:bg-slate-100' ?>" href="manageProducts.php<?= admin_html(addQueryToFilter($queryFilters, ['page' => $prevPage])) ?>"><span class="material-symbols-outlined">chevron_left</span></a>
                    <?php for ($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++): ?>
                        <a class="w-10 h-10 rounded-full flex items-center justify-center <?= $p === $currentPage ? 'bg-[#78555e] text-white' : 'hover:bg-slate-100' ?>" href="manageProducts.php<?= admin_html(addQueryToFilter($queryFilters, ['page' => $p])) ?>"><?= $p ?></a>
                    <?php endfor; ?>
                    <a class="w-10 h-10 rounded-full border border-slate-300 flex items-center justify-center <?= $currentPage >= $totalPages ? 'pointer-events-none opacity-50' : 'hover:bg-slate-100' ?>" href="manageProducts.php<?= admin_html(addQueryToFilter($queryFilters, ['page' => $nextPage])) ?>"><span class="material-symbols-outlined">chevron_right</span></a>
                </div>
                <form method="get" class="flex items-center gap-2">
                    <input type="hidden" name="q" value="<?= admin_html($queryFilters['q']) ?>" />
                    <input type="hidden" name="category_id" value="<?= (int)$queryFilters['category_id'] ?>" />
                    <input type="hidden" name="status" value="<?= admin_html($queryFilters['status']) ?>" />
                    <input type="hidden" name="stock" value="<?= admin_html($queryFilters['stock']) ?>" />
                    <input type="hidden" name="page" value="1" />
                    <span class="text-sm text-slate-500">Rows:</span>
                    <select class="rounded-full border-slate-300 text-sm" name="per_page" onchange="this.form.submit()">
                        <?php foreach ([10, 25, 50, 100] as $size): ?>
                            <option value="<?= $size ?>" <?= $queryFilters['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>

        <?php if ($variantProduct !== null): ?>
            <div class="mt-8 bg-white rounded-2xl soft-shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-[#78555e]">Variants: <?= admin_html((string)$variantProduct['name']) ?></h3>
                    <a class="text-sm text-slate-500 hover:underline" href="manageProducts.php<?= admin_html($baseQuery) ?>">Close</a>
                </div>

                <form class="grid grid-cols-1 md:grid-cols-6 gap-3 mb-6" method="post">
                    <input type="hidden" name="csrf_token" value="<?= admin_html($csrfToken) ?>" />
                    <input type="hidden" name="action" value="create_variant" />
                    <input type="hidden" name="product_id" value="<?= (int)$variantProduct['id'] ?>" />
                    <input type="hidden" name="return_query" value="<?= admin_html(addQueryToFilter($queryFilters, ['variant_product_id' => (int)$variantProduct['id']])) ?>" />

                    <input class="rounded-xl border-slate-300" name="variant_name" placeholder="Variant Name" required type="text" />
                    <input class="rounded-xl border-slate-300" name="sku" placeholder="SKU" required type="text" />
                    <input class="rounded-xl border-slate-300" min="0" name="price" placeholder="Price" required step="0.01" type="number" />
                    <input class="rounded-xl border-slate-300" min="0" name="stock_quantity" placeholder="Stock" required step="1" type="number" />
                    <input class="rounded-xl border-slate-300" min="0" name="low_stock_threshold" placeholder="Low Stock Alert" step="1" type="number" value="5" />
                    <button class="bg-[#78555e] text-white rounded-xl px-4 py-2 font-semibold" type="submit">Add Variant</button>
                </form>

                <?php if ($editingVariant !== null): ?>
                    <form class="grid grid-cols-1 md:grid-cols-7 gap-3 mb-6 bg-slate-50 rounded-xl p-4" method="post">
                        <input type="hidden" name="csrf_token" value="<?= admin_html($csrfToken) ?>" />
                        <input type="hidden" name="action" value="update_variant" />
                        <input type="hidden" name="variant_id" value="<?= (int)$editingVariant['id'] ?>" />
                        <input type="hidden" name="product_id" value="<?= (int)$variantProduct['id'] ?>" />
                        <input type="hidden" name="return_query" value="<?= admin_html(addQueryToFilter($queryFilters, ['variant_product_id' => (int)$variantProduct['id']])) ?>" />

                        <input class="rounded-xl border-slate-300" name="variant_name" placeholder="Variant Name" type="text" value="<?= admin_html((string)$editingVariant['variant_name']) ?>" />
                        <input class="rounded-xl border-slate-300" name="sku" placeholder="SKU" required type="text" value="<?= admin_html((string)$editingVariant['sku']) ?>" />
                        <input class="rounded-xl border-slate-300" min="0" name="price" step="0.01" type="number" value="<?= admin_html((string)$editingVariant['price']) ?>" />
                        <input class="rounded-xl border-slate-300" min="0" name="stock_quantity" step="1" type="number" value="<?= (int)$editingVariant['stock_quantity'] ?>" />
                        <input class="rounded-xl border-slate-300" min="0" name="low_stock_threshold" step="1" type="number" value="<?= (int)$editingVariant['low_stock_threshold'] ?>" />
                        <label class="flex items-center gap-2 text-sm"><input class="rounded border-slate-300" name="is_active" type="checkbox" value="1" <?= (int)$editingVariant['is_active'] === 1 ? 'checked' : '' ?> /> Active</label>
                        <div class="flex gap-2">
                            <button class="bg-slate-900 text-white rounded-xl px-4 py-2 font-semibold" type="submit">Save Variant</button>
                            <a class="rounded-xl px-4 py-2 border border-slate-300 text-sm text-slate-700" href="manageProducts.php<?= admin_html(addQueryToFilter($queryFilters, ['variant_product_id' => (int)$variantProduct['id']])) ?>">Cancel</a>
                        </div>
                    </form>
                <?php endif; ?>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-2 text-left">Variant</th>
                                <th class="px-3 py-2 text-left">SKU</th>
                                <th class="px-3 py-2 text-left">Price</th>
                                <th class="px-3 py-2 text-left">Stock</th>
                                <th class="px-3 py-2 text-left">Low Threshold</th>
                                <th class="px-3 py-2 text-left">Active</th>
                                <th class="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (count($variants) === 0): ?>
                                <tr><td class="px-3 py-4 text-slate-500" colspan="7">No variants yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($variants as $variant): ?>
                                <tr>
                                    <td class="px-3 py-2"><?= admin_html((string)$variant['variant_name']) ?></td>
                                    <td class="px-3 py-2"><?= admin_html((string)$variant['sku']) ?></td>
                                    <td class="px-3 py-2">$<?= number_format((float)$variant['price'], 2) ?></td>
                                    <td class="px-3 py-2"><?= number_format((int)$variant['stock_quantity']) ?></td>
                                    <td class="px-3 py-2"><?= number_format((int)$variant['low_stock_threshold']) ?></td>
                                    <td class="px-3 py-2"><?= (int)$variant['is_active'] === 1 ? 'Yes' : 'No' ?></td>
                                    <td class="px-3 py-2 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a class="px-3 py-1 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200" href="manageProducts.php<?= admin_html(addQueryToFilter($queryFilters, ['variant_product_id' => (int)$variantProduct['id'], 'variant_edit_id' => (int)$variant['id']])) ?>">Edit</a>
                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?= admin_html($csrfToken) ?>" />
                                                <input type="hidden" name="action" value="toggle_variant" />
                                                <input type="hidden" name="variant_id" value="<?= (int)$variant['id'] ?>" />
                                                <input type="hidden" name="product_id" value="<?= (int)$variantProduct['id'] ?>" />
                                                <input type="hidden" name="is_active" value="<?= (int)$variant['is_active'] === 1 ? 0 : 1 ?>" />
                                                <input type="hidden" name="return_query" value="<?= admin_html(addQueryToFilter($queryFilters, ['variant_product_id' => (int)$variantProduct['id']])) ?>" />
                                                <button class="px-3 py-1 rounded-lg bg-amber-100 text-amber-700 text-xs font-semibold" type="submit"><?= (int)$variant['is_active'] === 1 ? 'Disable' : 'Enable' ?></button>
                                            </form>
                                            <form method="post" onsubmit="return confirm('Delete this variant?')">
                                                <input type="hidden" name="csrf_token" value="<?= admin_html($csrfToken) ?>" />
                                                <input type="hidden" name="action" value="delete_variant" />
                                                <input type="hidden" name="variant_id" value="<?= (int)$variant['id'] ?>" />
                                                <input type="hidden" name="product_id" value="<?= (int)$variantProduct['id'] ?>" />
                                                <input type="hidden" name="return_query" value="<?= admin_html(addQueryToFilter($queryFilters, ['variant_product_id' => (int)$variantProduct['id']])) ?>" />
                                                <button class="px-3 py-1 rounded-lg bg-red-100 text-red-700 text-xs font-semibold" type="submit">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </section>
</main>

<a class="fixed bottom-8 right-8 w-14 h-14 rounded-full bg-[#78555e] text-white shadow-xl hover:scale-105 transition-all flex items-center justify-center z-50" href="manageProducts.php<?= admin_html($baseQuery) ?>&mode=create" title="Quick Add Product">
    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">add</span>
</a>
</body>
</html>

<?php

function handleProductPostActions(PDO $pdo, array $admin, array $statusOptions): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    if (!admin_validate_csrf_token($csrfToken)) {
        setAdminFlash('error', 'Invalid form token. Please try again.');
        redirectManageProducts((string)($_POST['return_query'] ?? ''));
    }

    $action = trim((string)($_POST['action'] ?? ''));

    try {
        switch ($action) {
            case 'create_product':
                $productId = createProduct($pdo, $admin, $statusOptions);
                setAdminFlash('success', 'Product created successfully.');
                admin_log_activity($pdo, (int)$admin['id'], 'product.create', 'Created product #' . $productId);
                redirectManageProducts((string)($_POST['return_query'] ?? ''));
                break;
            case 'update_product':
                $productId = updateProduct($pdo, $statusOptions);
                setAdminFlash('success', 'Product updated successfully.');
                admin_log_activity($pdo, (int)$admin['id'], 'product.update', 'Updated product #' . $productId);
                redirectManageProducts((string)($_POST['return_query'] ?? ''));
                break;
            case 'archive_product':
                $productId = max(0, (int)($_POST['product_id'] ?? 0));
                runProductStatusUpdate($pdo, $productId, 'archived');
                setAdminFlash('success', 'Product archived.');
                admin_log_activity($pdo, (int)$admin['id'], 'product.archive', 'Archived product #' . $productId);
                redirectManageProducts((string)($_POST['return_query'] ?? ''));
                break;
            case 'restore_product':
                $productId = max(0, (int)($_POST['product_id'] ?? 0));
                runProductStatusUpdate($pdo, $productId, 'active');
                setAdminFlash('success', 'Product restored to active.');
                admin_log_activity($pdo, (int)$admin['id'], 'product.restore', 'Restored product #' . $productId);
                redirectManageProducts((string)($_POST['return_query'] ?? ''));
                break;
            case 'delete_product':
                $productId = max(0, (int)($_POST['product_id'] ?? 0));
                deleteProduct($pdo, $productId);
                setAdminFlash('success', 'Product deleted permanently.');
                admin_log_activity($pdo, (int)$admin['id'], 'product.delete', 'Deleted product #' . $productId);
                redirectManageProducts((string)($_POST['return_query'] ?? ''));
                break;
            case 'create_variant':
                $variantId = createVariant($pdo);
                setAdminFlash('success', 'Variant created.');
                admin_log_activity($pdo, (int)$admin['id'], 'variant.create', 'Created variant #' . $variantId);
                redirectManageProducts((string)($_POST['return_query'] ?? ''));
                break;
            case 'create_category':
                $categoryId = createCategory($pdo);
                setAdminFlash('success', 'Category created.');
                admin_log_activity($pdo, (int)$admin['id'], 'category.create', 'Created category #' . $categoryId);
                redirectManageProducts((string)($_POST['return_query'] ?? ''));
                break;
            case 'create_brand':
                $brandId = createBrand($pdo);
                setAdminFlash('success', 'Brand created.');
                admin_log_activity($pdo, (int)$admin['id'], 'brand.create', 'Created brand #' . $brandId);
                redirectManageProducts((string)($_POST['return_query'] ?? ''));
                break;
            case 'update_variant':
                $variantId = updateVariant($pdo);
                setAdminFlash('success', 'Variant updated.');
                admin_log_activity($pdo, (int)$admin['id'], 'variant.update', 'Updated variant #' . $variantId);
                redirectManageProducts((string)($_POST['return_query'] ?? ''));
                break;
            case 'delete_variant':
                $variantId = deleteVariant($pdo);
                setAdminFlash('success', 'Variant deleted.');
                admin_log_activity($pdo, (int)$admin['id'], 'variant.delete', 'Deleted variant #' . $variantId);
                redirectManageProducts((string)($_POST['return_query'] ?? ''));
                break;
            case 'toggle_variant':
                $variantId = toggleVariant($pdo);
                setAdminFlash('success', 'Variant status updated.');
                admin_log_activity($pdo, (int)$admin['id'], 'variant.toggle', 'Toggled variant #' . $variantId);
                redirectManageProducts((string)($_POST['return_query'] ?? ''));
                break;
            default:
                setAdminFlash('error', 'Unknown action.');
                redirectManageProducts((string)($_POST['return_query'] ?? ''));
        }
    } catch (Throwable $exception) {
        setAdminFlash('error', $exception->getMessage());
        redirectManageProducts((string)($_POST['return_query'] ?? ''));
    }
}

function createProduct(PDO $pdo, array $admin, array $statusOptions): int
{
    $name = trim((string)($_POST['name'] ?? ''));
    if ($name === '') {
        throw new InvalidArgumentException('Product name is required.');
    }

    $slugInput = trim((string)($_POST['slug'] ?? ''));
    $slug = buildUniqueProductSlug($pdo, $slugInput !== '' ? $slugInput : $name, null);

    $status = trim((string)($_POST['status'] ?? 'draft'));
    if (!in_array($status, $statusOptions, true)) {
        $status = 'draft';
    }

    $categoryId = normalizeForeignId($_POST['category_id'] ?? null);
    $brandId = normalizeForeignId($_POST['brand_id'] ?? null);

    $statement = $pdo->prepare(
        'INSERT INTO products (category_id, brand_id, name, slug, short_description, description, status, created_by, created_at, updated_at)
         VALUES (:category_id, :brand_id, :name, :slug, :short_description, :description, :status, :created_by, NOW(), NOW())'
    );
    $statement->execute([
        ':category_id' => $categoryId,
        ':brand_id' => $brandId,
        ':name' => $name,
        ':slug' => $slug,
        ':short_description' => nullIfEmpty((string)($_POST['short_description'] ?? '')),
        ':description' => nullIfEmpty((string)($_POST['description'] ?? '')),
        ':status' => $status,
        ':created_by' => (int)$admin['id'],
    ]);

    $productId = (int)$pdo->lastInsertId();
    if ($productId <= 0) {
        throw new RuntimeException('Failed to create product.');
    }

    $initialSku = trim((string)($_POST['initial_sku'] ?? ''));
    $initialPrice = (float)($_POST['initial_price'] ?? 0);
    $initialStock = max(0, (int)($_POST['initial_stock'] ?? 0));

    if ($initialSku !== '' || $initialPrice > 0 || $initialStock > 0) {
        if ($initialSku === '') {
            $initialSku = strtoupper(substr($slug, 0, 8)) . '-001';
        }

        $variantStatement = $pdo->prepare(
            'INSERT INTO product_variants (product_id, sku, variant_name, price, stock_quantity, low_stock_threshold, is_active, created_at, updated_at)
             VALUES (:product_id, :sku, :variant_name, :price, :stock_quantity, :low_stock_threshold, 1, NOW(), NOW())'
        );
        $variantStatement->execute([
            ':product_id' => $productId,
            ':sku' => $initialSku,
            ':variant_name' => 'Default',
            ':price' => max(0.0, $initialPrice),
            ':stock_quantity' => $initialStock,
            ':low_stock_threshold' => 5,
        ]);
    }

    return $productId;
}

function updateProduct(PDO $pdo, array $statusOptions): int
{
    $productId = max(0, (int)($_POST['product_id'] ?? 0));
    if ($productId <= 0) {
        throw new InvalidArgumentException('Invalid product ID.');
    }

    $existing = fetchProductById($pdo, $productId);
    if ($existing === null) {
        throw new RuntimeException('Product not found.');
    }

    $name = trim((string)($_POST['name'] ?? ''));
    if ($name === '') {
        throw new InvalidArgumentException('Product name is required.');
    }

    $slugInput = trim((string)($_POST['slug'] ?? ''));
    $slug = buildUniqueProductSlug($pdo, $slugInput !== '' ? $slugInput : $name, $productId);

    $status = trim((string)($_POST['status'] ?? 'draft'));
    if (!in_array($status, $statusOptions, true)) {
        $status = 'draft';
    }

    $statement = $pdo->prepare(
        'UPDATE products
         SET category_id = :category_id,
             brand_id = :brand_id,
             name = :name,
             slug = :slug,
             short_description = :short_description,
             description = :description,
             status = :status,
             updated_at = NOW()
         WHERE id = :id'
    );
    $statement->execute([
        ':category_id' => normalizeForeignId($_POST['category_id'] ?? null),
        ':brand_id' => normalizeForeignId($_POST['brand_id'] ?? null),
        ':name' => $name,
        ':slug' => $slug,
        ':short_description' => nullIfEmpty((string)($_POST['short_description'] ?? '')),
        ':description' => nullIfEmpty((string)($_POST['description'] ?? '')),
        ':status' => $status,
        ':id' => $productId,
    ]);

    return $productId;
}

function runProductStatusUpdate(PDO $pdo, int $productId, string $status): void
{
    if ($productId <= 0) {
        throw new InvalidArgumentException('Invalid product ID.');
    }

    $statement = $pdo->prepare('UPDATE products SET status = :status, updated_at = NOW() WHERE id = :id');
    $statement->execute([':status' => $status, ':id' => $productId]);
}

function deleteProduct(PDO $pdo, int $productId): void
{
    if ($productId <= 0) {
        throw new InvalidArgumentException('Invalid product ID.');
    }

    $inUseCount = 0;
    if (admin_table_exists($pdo, 'order_items')) {
        $check = $pdo->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = :product_id');
        $check->execute([':product_id' => $productId]);
        $inUseCount = (int)$check->fetchColumn();
    }
    if ($inUseCount > 0) {
        throw new RuntimeException('Cannot delete product with historical order items. Archive it instead.');
    }

    $pdo->beginTransaction();
    try {
        if (admin_table_exists($pdo, 'product_images')) {
            $stmt1 = $pdo->prepare('DELETE FROM product_images WHERE product_id = :product_id');
            $stmt1->execute([':product_id' => $productId]);
        }

        if (admin_table_exists($pdo, 'product_variants')) {
            $stmt2 = $pdo->prepare('DELETE FROM product_variants WHERE product_id = :product_id');
            $stmt2->execute([':product_id' => $productId]);
        }

        $stmt3 = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $stmt3->execute([':id' => $productId]);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function createVariant(PDO $pdo): int
{
    $productId = max(0, (int)($_POST['product_id'] ?? 0));
    if ($productId <= 0) {
        throw new InvalidArgumentException('Invalid product for variant.');
    }

    $sku = trim((string)($_POST['sku'] ?? ''));
    if ($sku === '') {
        throw new InvalidArgumentException('Variant SKU is required.');
    }

    $statement = $pdo->prepare(
        'INSERT INTO product_variants
        (product_id, sku, variant_name, price, stock_quantity, low_stock_threshold, is_active, created_at, updated_at)
        VALUES
        (:product_id, :sku, :variant_name, :price, :stock_quantity, :low_stock_threshold, 1, NOW(), NOW())'
    );
    $statement->execute([
        ':product_id' => $productId,
        ':sku' => $sku,
        ':variant_name' => nullIfEmpty((string)($_POST['variant_name'] ?? '')) ?? 'Default',
        ':price' => max(0.0, (float)($_POST['price'] ?? 0)),
        ':stock_quantity' => max(0, (int)($_POST['stock_quantity'] ?? 0)),
        ':low_stock_threshold' => max(0, (int)($_POST['low_stock_threshold'] ?? 0)),
    ]);

    return (int)$pdo->lastInsertId();
}

function createCategory(PDO $pdo): int
{
    $name = trim((string)($_POST['name'] ?? ''));
    if ($name === '') {
        throw new InvalidArgumentException('Category name is required.');
    }

    $slug = slugify($name);
    if ($slug === '') {
        throw new InvalidArgumentException('Category name is invalid.');
    }

    $uniqueSlug = $slug;
    $suffix = 2;
    while (true) {
        $check = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE slug = :slug');
        $check->execute([':slug' => $uniqueSlug]);
        if ((int)$check->fetchColumn() === 0) {
            break;
        }
        $uniqueSlug = $slug . '-' . $suffix;
        $suffix++;
    }

    $statement = $pdo->prepare(
        'INSERT INTO categories (parent_id, name, slug, is_active, sort_order, created_at, updated_at)
         VALUES (NULL, :name, :slug, 1, 0, NOW(), NOW())'
    );
    $statement->execute([
        ':name' => $name,
        ':slug' => $uniqueSlug,
    ]);

    return (int)$pdo->lastInsertId();
}

function createBrand(PDO $pdo): int
{
    $name = trim((string)($_POST['name'] ?? ''));
    if ($name === '') {
        throw new InvalidArgumentException('Brand name is required.');
    }

    $slug = slugify($name);
    if ($slug === '') {
        throw new InvalidArgumentException('Brand name is invalid.');
    }

    $uniqueSlug = $slug;
    $suffix = 2;
    while (true) {
        $check = $pdo->prepare('SELECT COUNT(*) FROM brands WHERE slug = :slug');
        $check->execute([':slug' => $uniqueSlug]);
        if ((int)$check->fetchColumn() === 0) {
            break;
        }
        $uniqueSlug = $slug . '-' . $suffix;
        $suffix++;
    }

    $statement = $pdo->prepare(
        'INSERT INTO brands (name, slug, is_active, created_at, updated_at)
         VALUES (:name, :slug, 1, NOW(), NOW())'
    );
    $statement->execute([
        ':name' => $name,
        ':slug' => $uniqueSlug,
    ]);

    return (int)$pdo->lastInsertId();
}

function updateVariant(PDO $pdo): int
{
    $variantId = max(0, (int)($_POST['variant_id'] ?? 0));
    if ($variantId <= 0) {
        throw new InvalidArgumentException('Invalid variant.');
    }

    $sku = trim((string)($_POST['sku'] ?? ''));
    if ($sku === '') {
        throw new InvalidArgumentException('SKU is required.');
    }

    $statement = $pdo->prepare(
        'UPDATE product_variants
         SET variant_name = :variant_name,
             sku = :sku,
             price = :price,
             stock_quantity = :stock_quantity,
             low_stock_threshold = :low_stock_threshold,
             is_active = :is_active,
             updated_at = NOW()
         WHERE id = :id'
    );
    $statement->execute([
        ':variant_name' => nullIfEmpty((string)($_POST['variant_name'] ?? '')),
        ':sku' => $sku,
        ':price' => max(0.0, (float)($_POST['price'] ?? 0)),
        ':stock_quantity' => max(0, (int)($_POST['stock_quantity'] ?? 0)),
        ':low_stock_threshold' => max(0, (int)($_POST['low_stock_threshold'] ?? 0)),
        ':is_active' => isset($_POST['is_active']) ? 1 : 0,
        ':id' => $variantId,
    ]);

    return $variantId;
}

function deleteVariant(PDO $pdo): int
{
    $variantId = max(0, (int)($_POST['variant_id'] ?? 0));
    if ($variantId <= 0) {
        throw new InvalidArgumentException('Invalid variant.');
    }

    $statement = $pdo->prepare('DELETE FROM product_variants WHERE id = :id');
    $statement->execute([':id' => $variantId]);

    return $variantId;
}

function toggleVariant(PDO $pdo): int
{
    $variantId = max(0, (int)($_POST['variant_id'] ?? 0));
    if ($variantId <= 0) {
        throw new InvalidArgumentException('Invalid variant.');
    }

    $isActive = (int)($_POST['is_active'] ?? 0) === 1 ? 1 : 0;

    $statement = $pdo->prepare('UPDATE product_variants SET is_active = :is_active, updated_at = NOW() WHERE id = :id');
    $statement->execute([
        ':is_active' => $isActive,
        ':id' => $variantId,
    ]);

    return $variantId;
}

function fetchAllCategories(PDO $pdo): array
{
    if (!admin_table_exists($pdo, 'categories')) {
        return [];
    }

    $rows = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();
    return is_array($rows) ? $rows : [];
}

function fetchAllBrands(PDO $pdo): array
{
    if (!admin_table_exists($pdo, 'brands')) {
        return [];
    }

    $rows = $pdo->query('SELECT id, name FROM brands ORDER BY name ASC')->fetchAll();
    return is_array($rows) ? $rows : [];
}

function fetchProductStats(PDO $pdo): array
{
    $stats = [
        'total_items' => 0,
        'low_stock' => 0,
        'active_items' => 0,
        'draft_items' => 0,
    ];

    if (!admin_table_exists($pdo, 'products')) {
        return $stats;
    }

    $stats['total_items'] = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    $stats['active_items'] = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
    $stats['draft_items'] = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'draft'")->fetchColumn();

    if (admin_table_exists($pdo, 'product_variants')) {
        $stats['low_stock'] = (int)$pdo
            ->query('SELECT COUNT(*) FROM product_variants WHERE is_active = 1 AND stock_quantity <= low_stock_threshold')
            ->fetchColumn();
    }

    return $stats;
}

function fetchProductList(PDO $pdo, array $filters): array
{
    if (!admin_table_exists($pdo, 'products')) {
        return ['rows' => [], 'total' => 0];
    }

    $params = [];
    $where = ['1=1'];

    if ($filters['q'] !== '') {
        $where[] = '(p.name LIKE :q OR p.slug LIKE :q OR EXISTS(SELECT 1 FROM product_variants v2 WHERE v2.product_id = p.id AND v2.sku LIKE :q))';
        $params[':q'] = '%' . $filters['q'] . '%';
    }

    if ((int)$filters['category_id'] > 0) {
        $where[] = 'p.category_id = :category_id';
        $params[':category_id'] = (int)$filters['category_id'];
    }

    if ($filters['status'] !== '') {
        $where[] = 'p.status = :status';
        $params[':status'] = $filters['status'];
    }

    if ($filters['stock'] === 'out') {
        $where[] = 'COALESCE(vs.stock_total, 0) = 0';
    } elseif ($filters['stock'] === 'low') {
        $where[] = 'COALESCE(vs.low_stock_count, 0) > 0';
    }

    $whereSql = implode(' AND ', $where);

    $countSql = "
        SELECT COUNT(*) FROM products p
        LEFT JOIN (
            SELECT product_id,
                   COALESCE(SUM(stock_quantity), 0) AS stock_total,
                   SUM(CASE WHEN is_active = 1 AND stock_quantity <= low_stock_threshold THEN 1 ELSE 0 END) AS low_stock_count
            FROM product_variants
            GROUP BY product_id
        ) vs ON vs.product_id = p.id
        WHERE {$whereSql}
    ";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $offset = max(0, ((int)$filters['page'] - 1) * (int)$filters['per_page']);

    $sql = "
        SELECT
            p.id,
            p.name,
            p.slug,
            p.status,
            p.category_id,
            p.brand_id,
            c.name AS category_name,
            b.name AS brand_name,
            COALESCE(vs.stock_total, 0) AS stock_total,
            COALESCE(vs.stock_target, 10) AS stock_target,
            vs.min_price,
            vs.max_price,
            img.image
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN brands b ON b.id = p.brand_id
        LEFT JOIN (
            SELECT
                product_id,
                COALESCE(SUM(stock_quantity), 0) AS stock_total,
                GREATEST(1, COALESCE(MAX(low_stock_threshold), 10)) AS stock_target,
                SUM(CASE WHEN is_active = 1 AND stock_quantity <= low_stock_threshold THEN 1 ELSE 0 END) AS low_stock_count,
                MIN(price) AS min_price,
                MAX(price) AS max_price
            FROM product_variants
            GROUP BY product_id
        ) vs ON vs.product_id = p.id
        LEFT JOIN (
            SELECT product_id, MIN(image) AS image
            FROM product_images
            GROUP BY product_id
        ) img ON img.product_id = p.id
        WHERE {$whereSql}
        ORDER BY p.updated_at DESC, p.id DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', (int)$filters['per_page'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();
    return ['rows' => is_array($rows) ? $rows : [], 'total' => $total];
}

function fetchProductById(PDO $pdo, int $productId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $productId]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function fetchVariantsByProductId(PDO $pdo, int $productId): array
{
    if (!admin_table_exists($pdo, 'product_variants')) {
        return [];
    }

    $stmt = $pdo->prepare('SELECT * FROM product_variants WHERE product_id = :product_id ORDER BY id ASC');
    $stmt->execute([':product_id' => $productId]);
    $rows = $stmt->fetchAll();
    return is_array($rows) ? $rows : [];
}

function fetchVariantById(PDO $pdo, int $variantId): ?array
{
    if (!admin_table_exists($pdo, 'product_variants')) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM product_variants WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $variantId]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function buildUniqueProductSlug(PDO $pdo, string $text, ?int $excludeId): string
{
    $base = slugify($text);
    if ($base === '') {
        $base = 'product';
    }

    $slug = $base;
    $suffix = 2;

    while (productSlugExists($pdo, $slug, $excludeId)) {
        $slug = $base . '-' . $suffix;
        $suffix++;
    }

    return $slug;
}

function productSlugExists(PDO $pdo, string $slug, ?int $excludeId): bool
{
    $sql = 'SELECT COUNT(*) FROM products WHERE slug = :slug';
    if ($excludeId !== null) {
        $sql .= ' AND id <> :exclude_id';
    }
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':slug', $slug);
    if ($excludeId !== null) {
        $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
    }
    $stmt->execute();
    return ((int)$stmt->fetchColumn()) > 0;
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

function normalizeForeignId(mixed $value): ?int
{
    $id = (int)$value;
    return $id > 0 ? $id : null;
}

function nullIfEmpty(string $value): ?string
{
    $trimmed = trim($value);
    return $trimmed === '' ? null : $trimmed;
}

function setAdminFlash(string $type, string $message): void
{
    $_SESSION['admin_products_flash'] = ['type' => $type, 'message' => $message];
}

function pullAdminFlash(): ?array
{
    if (!isset($_SESSION['admin_products_flash']) || !is_array($_SESSION['admin_products_flash'])) {
        return null;
    }

    $flash = $_SESSION['admin_products_flash'];
    unset($_SESSION['admin_products_flash']);
    return $flash;
}

function redirectManageProducts(string $returnQuery): never
{
    $safeQuery = sanitizeReturnQuery($returnQuery);
    header('Location: manageProducts.php' . $safeQuery);
    exit;
}

function sanitizeReturnQuery(string $query): string
{
    $query = trim($query);
    if ($query === '') {
        return '';
    }
    if ($query[0] !== '?') {
        $query = '?' . ltrim($query, '?');
    }
    return $query;
}

function buildFilterQuery(array $filters): string
{
    $query = http_build_query([
        'q' => (string)$filters['q'],
        'category_id' => (int)$filters['category_id'],
        'status' => (string)$filters['status'],
        'stock' => (string)$filters['stock'],
        'per_page' => (int)$filters['per_page'],
        'page' => (int)$filters['page'],
    ]);
    return $query === '' ? '' : '?' . $query;
}

function addQueryToFilter(array $filters, array $override): string
{
    $merged = array_merge($filters, $override);
    $query = http_build_query($merged);
    return $query === '' ? '' : '?' . $query;
}

function productStatusBadgeClass(string $status): string
{
    return match ($status) {
        'active' => 'bg-green-100 text-green-700',
        'draft' => 'bg-amber-100 text-amber-700',
        'inactive' => 'bg-slate-200 text-slate-700',
        'archived' => 'bg-red-100 text-red-700',
        default => 'bg-slate-100 text-slate-700',
    };
}
