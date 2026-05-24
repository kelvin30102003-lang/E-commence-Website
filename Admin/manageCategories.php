<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_layout.php';

admin_start_session();
$admin = admin_require_auth('adminLogin.php');
admin_page_cache_start($admin, 'categories', ADMIN_PAGE_CACHE_TTL_SECONDS);

$pdo = admin_db();
admin_ensure_tables($pdo);
ensureCategoriesTable($pdo);

$csrfToken = admin_bootstrap_csrf_token();

$queryFilters = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
    'parent_id' => (int)($_GET['parent_id'] ?? 0),
    'per_page' => max(5, min(100, (int)($_GET['per_page'] ?? 10))),
    'page' => max(1, (int)($_GET['page'] ?? 1)),
];

$mode = trim((string)($_GET['mode'] ?? ''));
$editId = max(0, (int)($_GET['edit_id'] ?? 0));

handleCategoryPostActions($pdo);

$flash = pullCategoryFlash();
$stats = fetchCategoryStats($pdo);

$listData = fetchCategoryList($pdo, $queryFilters);
$categories = $listData['rows'];
$totalRows = $listData['total'];
$totalPages = max(1, (int)ceil($totalRows / $queryFilters['per_page']));
$currentPage = min($queryFilters['page'], $totalPages);

if ($currentPage !== $queryFilters['page']) {
    $queryFilters['page'] = $currentPage;
    $listData = fetchCategoryList($pdo, $queryFilters);
    $categories = $listData['rows'];
}

$editingCategory = null;
if ($mode === 'edit' && $editId > 0) {
    $editingCategory = fetchCategoryById($pdo, $editId);
    if ($editingCategory === null) {
        setCategoryFlash('error', 'Category not found for editing.');
        header('Location: manageCategories.php');
        exit;
    }
}

$parentOptions = fetchParentCategoryOptions($pdo, $editingCategory !== null ? (int)$editingCategory['id'] : null);
$baseQuery = buildCategoryFilterQuery($queryFilters);
$showForm = ($mode === 'create') || ($mode === 'edit' && $editingCategory !== null);

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Manage Categories | LuvShop Admin</title>
    <?php admin_render_critical_css(); ?>
    <?php $adminCssHref = admin_css_href(); ?>
<?php if ($adminCssHref !== null): ?>
    <link href="<?= admin_html($adminCssHref) ?>" rel="stylesheet"/>
<?php endif; ?>
    <link href="<?= admin_html(admin_material_symbols_href()) ?>" rel="stylesheet"/>
    <style>
        body { font-family: "Plus Jakarta Sans", sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        .drag-handle:active { cursor: grabbing; }
        .pastel-gradient-hover:hover {
            background: linear-gradient(135deg, rgba(255, 209, 220, 0.4) 0%, rgba(233, 221, 171, 0.4) 100%);
        }
        .bubbly-interaction {
            transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .bubbly-interaction:active { transform: scale(0.92); }
    </style>
</head>
<body class="bg-surface font-body-md text-on-surface">
<?php admin_render_sidebar($admin, 'categories'); ?>

<main class="ml-64 min-h-screen flex flex-col" id="app-main">
    <?php
    admin_render_header($admin, [
        'search_action' => 'manageCategories.php',
        'search_method' => 'get',
        'search_name' => 'q',
        'search_value' => trim((string)$queryFilters['q']),
        'search_placeholder' => 'Search categories...',
        'search_hidden' => [
            'status' => (string)$queryFilters['status'],
            'per_page' => (string)$queryFilters['per_page'],
            'page' => '1',
        ],
    ]);
    ?>

    <div class="p-lg space-y-lg">
        <section class="flex flex-col md:flex-row md:items-center md:justify-between gap-md">
            <div>
                <h2 class="font-display-lg text-headline-lg text-primary tracking-tight">Product Categories</h2>
                <p class="text-on-surface-variant">Organize your catalog structure and visibility.</p>
            </div>
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-sm">
                <form class="flex items-center gap-sm" method="get">
                    <input name="q" type="hidden" value="<?= admin_html($queryFilters['q']) ?>"/>
                    <input name="per_page" type="hidden" value="<?= (int)$queryFilters['per_page'] ?>"/>
                    <input name="page" type="hidden" value="1"/>
                    <select class="bg-surface-container-low border-none rounded-full px-md py-sm focus:ring-2 focus:ring-primary-container" name="status">
                        <option value="" <?= $queryFilters['status'] === '' ? 'selected' : '' ?>>All Status</option>
                        <option value="active" <?= $queryFilters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $queryFilters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <button class="bubbly-interaction bg-primary text-on-primary px-lg py-md rounded-full flex items-center justify-center gap-sm shadow-sm hover:bg-primary/90" type="submit">
                        <span class="material-symbols-outlined">filter_list</span>
                        <span class="font-label-md">Filter</span>
                    </button>
                </form>
                <a class="bubbly-interaction bg-primary text-on-primary px-lg py-md rounded-full flex items-center justify-center gap-sm shadow-sm hover:bg-primary/90" href="manageCategories.php<?= admin_html($baseQuery) ?>&mode=create">
                    <span class="material-symbols-outlined">add_circle</span>
                    <span class="font-label-md">Create Category</span>
                </a>
            </div>
        </section>

        <?php if ($flash !== null): ?>
            <div class="rounded-lg px-lg py-md border <?= $flash['type'] === 'error' ? 'bg-error-container border-red-200 text-on-error-container' : 'bg-secondary-container border-green-200 text-on-secondary-container' ?>">
                <?= admin_html($flash['message']) ?>
            </div>
        <?php endif; ?>

        <?php if ($showForm): ?>
            <section class="bg-surface-container-lowest rounded-lg shadow-md border border-outline-variant/10 p-lg">
                <h3 class="font-headline-md text-primary mb-md"><?= $mode === 'edit' ? 'Edit Category' : 'Create Category' ?></h3>
                <form class="grid grid-cols-1 md:grid-cols-2 gap-md" method="post">
                    <input type="hidden" name="csrf_token" value="<?= admin_html($csrfToken) ?>"/>
                    <input type="hidden" name="action" value="<?= $mode === 'edit' ? 'update_category' : 'create_category' ?>"/>
                    <input type="hidden" name="return_query" value="<?= admin_html($baseQuery) ?>"/>
                    <?php if ($mode === 'edit' && $editingCategory !== null): ?>
                        <input type="hidden" name="category_id" value="<?= (int)$editingCategory['id'] ?>"/>
                    <?php endif; ?>

                    <div>
                        <label class="block text-sm font-semibold mb-1">Category Name</label>
                        <input class="w-full rounded-xl border-outline-variant" name="name" required type="text" value="<?= admin_html((string)($editingCategory['name'] ?? '')) ?>"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Slug (optional)</label>
                        <input class="w-full rounded-xl border-outline-variant" name="slug" type="text" value="<?= admin_html((string)($editingCategory['slug'] ?? '')) ?>"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Parent Category</label>
                        <select class="w-full rounded-xl border-outline-variant" name="parent_id">
                            <option value="0">No Parent</option>
                            <?php foreach ($parentOptions as $parentOption): ?>
                                <option value="<?= (int)$parentOption['id'] ?>" <?= (int)($editingCategory['parent_id'] ?? 0) === (int)$parentOption['id'] ? 'selected' : '' ?>>
                                    <?= admin_html((string)$parentOption['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Sort Order</label>
                        <input class="w-full rounded-xl border-outline-variant" min="0" name="sort_order" step="1" type="number" value="<?= (int)($editingCategory['sort_order'] ?? 0) ?>"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Image URL (optional)</label>
                        <input class="w-full rounded-xl border-outline-variant" name="image" placeholder="https://..." type="text" value="<?= admin_html((string)($editingCategory['image'] ?? '')) ?>"/>
                    </div>
                    <div class="flex items-center gap-2 pt-6">
                        <input class="rounded border-outline-variant" id="is_active" name="is_active" type="checkbox" value="1" <?= ((int)($editingCategory['is_active'] ?? 1) === 1) ? 'checked' : '' ?>/>
                        <label for="is_active">Active</label>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold mb-1">Description (optional)</label>
                        <textarea class="w-full rounded-xl border-outline-variant" name="description" rows="3"><?= admin_html((string)($editingCategory['description'] ?? '')) ?></textarea>
                    </div>
                    <div class="md:col-span-2 flex items-center gap-sm">
                        <button class="bg-primary text-on-primary px-lg py-sm rounded-full font-label-md" type="submit">
                            <?= $mode === 'edit' ? 'Update Category' : 'Create Category' ?>
                        </button>
                        <a class="px-lg py-sm rounded-full border border-outline-variant text-on-surface-variant" href="manageCategories.php<?= admin_html($baseQuery) ?>">Cancel</a>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <section class="grid grid-cols-1 md:grid-cols-4 gap-lg">
            <div class="bg-surface-container-lowest p-lg rounded-lg shadow-sm border border-outline-variant/10 flex flex-col gap-xs">
                <span class="text-on-surface-variant font-label-md">Total Categories</span>
                <span class="font-display-lg text-headline-lg text-primary"><?= number_format((int)$stats['total_categories']) ?></span>
                <div class="mt-2 text-secondary text-xs flex items-center font-bold">
                    <span class="material-symbols-outlined text-sm">trending_up</span> +<?= number_format((int)$stats['created_this_month']) ?> this month
                </div>
            </div>
            <div class="bg-surface-container-lowest p-lg rounded-lg shadow-sm border border-outline-variant/10 flex flex-col gap-xs">
                <span class="text-on-surface-variant font-label-md">Active Status</span>
                <span class="font-display-lg text-headline-lg text-secondary"><?= number_format((int)$stats['active_categories']) ?></span>
                <div class="mt-2 text-on-surface-variant text-xs font-bold">
                    <?= (int)$stats['total_categories'] > 0 ? number_format(((int)$stats['active_categories'] / (int)$stats['total_categories']) * 100, 1) : '0.0' ?>% Engagement Rate
                </div>
            </div>
            <div class="bg-tertiary-fixed p-lg rounded-lg shadow-sm border border-outline-variant/10 flex flex-col gap-xs">
                <span class="text-on-tertiary-fixed-variant font-label-md">Top Performing</span>
                <span class="font-display-lg text-headline-lg text-on-tertiary-fixed"><?= admin_html((string)($stats['top_category_name'] ?? '—')) ?></span>
                <div class="mt-2 text-on-tertiary-fixed-variant text-xs font-bold"><?= number_format((int)$stats['top_category_products']) ?> Products linked</div>
            </div>
            <div class="bg-primary-container p-lg rounded-lg shadow-sm border border-outline-variant/10 flex flex-col gap-xs">
                <span class="text-on-primary-container font-label-md">Drafts</span>
                <span class="font-display-lg text-headline-lg text-on-primary-fixed"><?= number_format((int)$stats['inactive_categories']) ?></span>
                <div class="mt-2 text-on-primary-fixed-variant text-xs font-bold">Currently Inactive</div>
            </div>
        </section>

        <section class="bg-surface-container-lowest rounded-lg shadow-md overflow-hidden border border-outline-variant/10">
            <div class="p-lg border-b border-outline-variant/10 flex flex-col md:flex-row md:justify-between md:items-center gap-sm bg-surface-container-low/50">
                <h3 class="font-headline-md text-primary">Category Hierarchy</h3>
                <div class="flex gap-sm">
                    <a class="flex items-center gap-xs px-md py-sm rounded-full bg-surface-container border border-outline-variant text-on-surface-variant hover:bg-surface-variant transition-colors" href="manageCategories.php<?= admin_html(buildCategoryFilterQuery(array_merge($queryFilters, ['status' => 'active', 'page' => 1]))) ?>">
                        <span class="material-symbols-outlined text-[20px]">filter_list</span>
                        <span class="font-label-md">Active</span>
                    </a>
                    <a class="flex items-center gap-xs px-md py-sm rounded-full bg-surface-container border border-outline-variant text-on-surface-variant hover:bg-surface-variant transition-colors" href="manageCategories.php<?= admin_html(buildCategoryFilterQuery(array_merge($queryFilters, ['q' => '', 'page' => 1]))) ?>">
                        <span class="material-symbols-outlined text-[20px]">restart_alt</span>
                        <span class="font-label-md">Reset</span>
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant font-label-md">
                        <th class="text-left px-lg py-md w-12"></th>
                        <th class="text-left px-lg py-md">Category</th>
                        <th class="text-left px-lg py-md">Parent</th>
                        <th class="text-left px-lg py-md">Status</th>
                        <th class="text-right px-lg py-md">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                    <?php if (count($categories) === 0): ?>
                        <tr>
                            <td class="px-lg py-xl text-center text-on-surface-variant" colspan="5">No categories found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($categories as $category): ?>
                        <?php
                        $productCount = (int)($category['product_count'] ?? 0);
                        $isActive = (int)($category['is_active'] ?? 0) === 1;
                        $name = (string)$category['name'];
                        $imageUrl = trim((string)($category['image'] ?? ''));
                        $parentName = trim((string)($category['parent_name'] ?? ''));
                        $isChild = (int)($category['parent_id'] ?? 0) > 0;
                        ?>
                        <tr class="pastel-gradient-hover transition-colors">
                            <td class="px-lg py-md text-center">
                                <span class="material-symbols-outlined drag-handle text-outline cursor-grab">drag_indicator</span>
                            </td>
                            <td class="px-lg py-md">
                                <div class="flex items-center gap-md <?= $isChild ? 'pl-lg' : '' ?>">
                                    <div class="w-12 h-12 rounded-xl bg-primary-fixed overflow-hidden flex-shrink-0 shadow-sm flex items-center justify-center text-primary font-bold">
                                        <?php if ($imageUrl !== ''): ?>
                                            <img alt="<?= admin_html($name) ?>" class="w-full h-full object-cover" decoding="async" fetchpriority="low" height="48" loading="lazy" src="<?= admin_html($imageUrl) ?>" width="48"/>
                                        <?php else: ?>
                                            <?= admin_html(strtoupper(substr($name, 0, 1))) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="font-bold text-on-surface"><?= admin_html($name) ?></p>
                                        <p class="text-xs text-on-surface-variant"><?= number_format($productCount) ?> Items · Slug: <?= admin_html((string)$category['slug']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-lg py-md">
                                <?php if ($parentName === ''): ?>
                                    <span class="text-on-surface-variant">—</span>
                                <?php else: ?>
                                    <div class="inline-flex items-center gap-xs text-on-surface-variant font-label-md bg-surface-container px-md py-xs rounded-full border border-outline-variant/20">
                                        <span class="material-symbols-outlined text-[16px]">subdirectory_arrow_right</span>
                                        <?= admin_html($parentName) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-lg py-md">
                                <form method="post">
                                    <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
                                    <input name="action" type="hidden" value="toggle_category"/>
                                    <input name="category_id" type="hidden" value="<?= (int)$category['id'] ?>"/>
                                    <input name="is_active" type="hidden" value="<?= $isActive ? 0 : 1 ?>"/>
                                    <input name="return_query" type="hidden" value="<?= admin_html($baseQuery) ?>"/>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input class="sr-only peer" onchange="this.form.submit()" <?= $isActive ? 'checked' : '' ?> type="checkbox"/>
                                        <div class="w-11 h-6 bg-surface-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-secondary"></div>
                                        <span class="ml-3 font-label-md <?= $isActive ? 'text-secondary' : 'text-on-surface-variant' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span>
                                    </label>
                                </form>
                            </td>
                            <td class="px-lg py-md text-right">
                                <div class="flex justify-end gap-xs">
                                    <a class="p-sm rounded-full text-on-surface-variant hover:bg-primary-container hover:text-primary transition-all bubbly-interaction" href="manageCategories.php<?= admin_html($baseQuery) ?>&mode=edit&edit_id=<?= (int)$category['id'] ?>">
                                        <span class="material-symbols-outlined">edit</span>
                                    </a>
                                    <form method="post" onsubmit="return confirm('Delete this category?');">
                                        <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
                                        <input name="action" type="hidden" value="delete_category"/>
                                        <input name="category_id" type="hidden" value="<?= (int)$category['id'] ?>"/>
                                        <input name="return_query" type="hidden" value="<?= admin_html($baseQuery) ?>"/>
                                        <button class="p-sm rounded-full text-on-surface-variant hover:bg-error-container hover:text-error transition-all bubbly-interaction" type="submit">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </form>
                                    <a class="p-sm rounded-full text-on-surface-variant hover:bg-tertiary-container hover:text-tertiary transition-all bubbly-interaction" href="manageProducts.php?category_id=<?= (int)$category['id'] ?>">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="w-full py-lg px-xl flex flex-col md:flex-row justify-between items-center bg-surface border-t border-outline-variant/10">
                <p class="font-label-sm text-label-sm text-on-surface-variant">
                    Showing <?= $totalRows === 0 ? 0 : (($currentPage - 1) * $queryFilters['per_page'] + 1) ?>
                    to <?= min($currentPage * $queryFilters['per_page'], $totalRows) ?>
                    of <?= number_format($totalRows) ?> categories
                </p>
                <div class="flex items-center gap-sm mt-md md:mt-0">
                    <?php if ($currentPage > 1): ?>
                        <a class="p-sm rounded-full bg-surface-container text-on-surface-variant" href="manageCategories.php<?= admin_html(buildCategoryFilterQuery(array_merge($queryFilters, ['page' => $currentPage - 1]))) ?>">
                            <span class="material-symbols-outlined">chevron_left</span>
                        </a>
                    <?php else: ?>
                        <button class="p-sm rounded-full bg-surface-container text-on-surface-variant disabled:opacity-50" disabled>
                            <span class="material-symbols-outlined">chevron_left</span>
                        </button>
                    <?php endif; ?>

                    <?php
                    $pageStart = max(1, $currentPage - 1);
                    $pageEnd = min($totalPages, $pageStart + 2);
                    $pageStart = max(1, $pageEnd - 2);
                    for ($page = $pageStart; $page <= $pageEnd; $page++):
                        ?>
                        <a class="w-8 h-8 rounded-full flex items-center justify-center font-label-sm <?= $page === $currentPage ? 'bg-primary text-on-primary' : 'hover:bg-primary-container text-on-surface-variant' ?>" href="manageCategories.php<?= admin_html(buildCategoryFilterQuery(array_merge($queryFilters, ['page' => $page]))) ?>">
                            <?= $page ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a class="p-sm rounded-full bg-surface-container text-on-surface-variant" href="manageCategories.php<?= admin_html(buildCategoryFilterQuery(array_merge($queryFilters, ['page' => $currentPage + 1]))) ?>">
                            <span class="material-symbols-outlined">chevron_right</span>
                        </a>
                    <?php else: ?>
                        <button class="p-sm rounded-full bg-surface-container text-on-surface-variant disabled:opacity-50" disabled>
                            <span class="material-symbols-outlined">chevron_right</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-2 gap-lg pb-xl">
            <div class="bg-primary-fixed p-lg rounded-lg shadow-sm flex items-start gap-lg border border-primary-container/20">
                <div class="bg-primary-container p-md rounded-full text-primary">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">lightbulb</span>
                </div>
                <div>
                    <h4 class="font-headline-md text-on-primary-fixed mb-xs">Curation Tip</h4>
                    <p class="text-on-primary-fixed-variant body-md">Use parent and child categories to improve catalog navigation and make customer filtering easier.</p>
                </div>
            </div>
            <div class="bg-secondary-container p-lg rounded-lg shadow-sm flex items-start gap-lg border border-secondary-fixed/20">
                <div class="bg-secondary-fixed p-md rounded-full text-secondary">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">auto_awesome</span>
                </div>
                <div>
                    <h4 class="font-headline-md text-on-secondary-container mb-xs">Smart Sorting</h4>
                    <p class="text-on-secondary-container body-md">Set sort order to control how categories appear in your storefront and keep key categories visible first.</p>
                </div>
            </div>
        </section>
    </div>

    <footer class="w-full py-lg px-xl flex flex-col md:flex-row justify-between items-center mt-auto bg-surface border-t border-outline-variant/10">
        <span class="font-label-sm text-label-sm text-primary">© <?= date('Y') ?> LuvShop Commerce. Crafted with love.</span>
        <div class="flex gap-lg mt-md md:mt-0">
            <a class="font-label-sm text-label-sm text-on-surface-variant hover:text-primary transition-colors" href="#">Support</a>
            <a class="font-label-sm text-label-sm text-on-surface-variant hover:text-primary transition-colors" href="#">Privacy Policy</a>
            <a class="font-label-sm text-label-sm text-on-surface-variant hover:text-primary transition-colors" href="#">Terms of Service</a>
        </div>
    </footer>
</main>

<script>
    (() => {
        const rows = Array.from(document.querySelectorAll('tbody tr'));
        rows.forEach((row) => {
            const handle = row.querySelector('.drag-handle');
            if (!handle) {
                return;
            }
            handle.addEventListener('mousedown', () => {
                row.classList.add('bg-primary-container/20', 'scale-[1.01]', 'shadow-lg', 'z-10');
            });
        });

        if (typeof window.__adminCategoriesMouseupHandler === 'function') {
            window.removeEventListener('mouseup', window.__adminCategoriesMouseupHandler);
        }
        const mouseupHandler = () => {
            rows.forEach((row) => {
                row.classList.remove('bg-primary-container/20', 'scale-[1.01]', 'shadow-lg', 'z-10');
            });
        };
        window.__adminCategoriesMouseupHandler = mouseupHandler;
        window.addEventListener('mouseup', mouseupHandler);

        const searchInput = document.querySelector('input[name="q"]');
        if (searchInput && searchInput.parentElement) {
            searchInput.addEventListener('focus', () => {
                searchInput.parentElement.classList.add('scale-105');
            });
            searchInput.addEventListener('blur', () => {
                searchInput.parentElement.classList.remove('scale-105');
            });
        }
    })();
</script>
</body>
</html>
<?php admin_page_cache_finish(); ?>

<?php

function ensureCategoriesTable(PDO $pdo): void
{
    if (admin_table_exists($pdo, 'categories')) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS categories (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            parent_id BIGINT UNSIGNED NULL,
            name VARCHAR(150) NOT NULL,
            slug VARCHAR(180) NOT NULL,
            description TEXT NULL,
            image VARCHAR(255) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY slug (slug),
            KEY idx_categories_parent_id (parent_id),
            KEY idx_categories_active (is_active),
            KEY idx_categories_sort_order (sort_order),
            CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function handleCategoryPostActions(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    if (!admin_validate_csrf_token($csrfToken)) {
        setCategoryFlash('error', 'Invalid form token. Please try again.');
        redirectManageCategories((string)($_POST['return_query'] ?? ''));
    }

    $action = trim((string)($_POST['action'] ?? ''));

    try {
        switch ($action) {
            case 'create_category':
                $categoryId = createCategoryRecord($pdo);
                setCategoryFlash('success', 'Category created (#' . $categoryId . ').');
                admin_log_activity($pdo, (int)(admin_current()['id'] ?? 0), 'category.create', 'Created category #' . $categoryId);
                redirectManageCategories((string)($_POST['return_query'] ?? ''));
                break;
            case 'update_category':
                $categoryId = updateCategoryRecord($pdo);
                setCategoryFlash('success', 'Category updated (#' . $categoryId . ').');
                admin_log_activity($pdo, (int)(admin_current()['id'] ?? 0), 'category.update', 'Updated category #' . $categoryId);
                redirectManageCategories((string)($_POST['return_query'] ?? ''));
                break;
            case 'toggle_category':
                $categoryId = toggleCategoryRecord($pdo);
                setCategoryFlash('success', 'Category status updated.');
                admin_log_activity($pdo, (int)(admin_current()['id'] ?? 0), 'category.toggle', 'Toggled category #' . $categoryId);
                redirectManageCategories((string)($_POST['return_query'] ?? ''));
                break;
            case 'delete_category':
                $categoryId = deleteCategoryRecord($pdo);
                setCategoryFlash('success', 'Category deleted.');
                admin_log_activity($pdo, (int)(admin_current()['id'] ?? 0), 'category.delete', 'Deleted category #' . $categoryId);
                redirectManageCategories((string)($_POST['return_query'] ?? ''));
                break;
            default:
                setCategoryFlash('error', 'Unknown action.');
                redirectManageCategories((string)($_POST['return_query'] ?? ''));
        }
    } catch (Throwable $exception) {
        setCategoryFlash('error', $exception->getMessage());
        redirectManageCategories((string)($_POST['return_query'] ?? ''));
    }
}

function createCategoryRecord(PDO $pdo): int
{
    $name = trim((string)($_POST['name'] ?? ''));
    if ($name === '') {
        throw new InvalidArgumentException('Category name is required.');
    }

    $slugInput = trim((string)($_POST['slug'] ?? ''));
    $slug = buildUniqueCategorySlug($pdo, $slugInput !== '' ? $slugInput : $name, null);
    $parentId = normalizeCategoryParentId($_POST['parent_id'] ?? null);
    $sortOrder = max(0, (int)($_POST['sort_order'] ?? 0));

    $statement = $pdo->prepare(
        'INSERT INTO categories (parent_id, name, slug, description, image, is_active, sort_order, created_at, updated_at)
         VALUES (:parent_id, :name, :slug, :description, :image, :is_active, :sort_order, NOW(), NOW())'
    );
    $statement->execute([
        ':parent_id' => $parentId,
        ':name' => $name,
        ':slug' => $slug,
        ':description' => nullIfEmptyCategory((string)($_POST['description'] ?? '')),
        ':image' => nullIfEmptyCategory((string)($_POST['image'] ?? '')),
        ':is_active' => isset($_POST['is_active']) ? 1 : 0,
        ':sort_order' => $sortOrder,
    ]);

    $id = (int)$pdo->lastInsertId();
    if ($id <= 0) {
        throw new RuntimeException('Failed to create category.');
    }

    return $id;
}

function updateCategoryRecord(PDO $pdo): int
{
    $categoryId = max(0, (int)($_POST['category_id'] ?? 0));
    if ($categoryId <= 0) {
        throw new InvalidArgumentException('Invalid category.');
    }

    $existing = fetchCategoryById($pdo, $categoryId);
    if ($existing === null) {
        throw new RuntimeException('Category not found.');
    }

    $name = trim((string)($_POST['name'] ?? ''));
    if ($name === '') {
        throw new InvalidArgumentException('Category name is required.');
    }

    $slugInput = trim((string)($_POST['slug'] ?? ''));
    $slug = buildUniqueCategorySlug($pdo, $slugInput !== '' ? $slugInput : $name, $categoryId);

    $parentId = normalizeCategoryParentId($_POST['parent_id'] ?? null);
    if ($parentId !== null) {
        if ($parentId === $categoryId) {
            throw new InvalidArgumentException('A category cannot be its own parent.');
        }
        if (isDescendantCategory($pdo, $parentId, $categoryId)) {
            throw new InvalidArgumentException('Parent category cannot be a child of this category.');
        }
    }

    $statement = $pdo->prepare(
        'UPDATE categories
         SET parent_id = :parent_id,
             name = :name,
             slug = :slug,
             description = :description,
             image = :image,
             is_active = :is_active,
             sort_order = :sort_order,
             updated_at = NOW()
         WHERE id = :id'
    );
    $statement->execute([
        ':parent_id' => $parentId,
        ':name' => $name,
        ':slug' => $slug,
        ':description' => nullIfEmptyCategory((string)($_POST['description'] ?? '')),
        ':image' => nullIfEmptyCategory((string)($_POST['image'] ?? '')),
        ':is_active' => isset($_POST['is_active']) ? 1 : 0,
        ':sort_order' => max(0, (int)($_POST['sort_order'] ?? 0)),
        ':id' => $categoryId,
    ]);

    return $categoryId;
}

function toggleCategoryRecord(PDO $pdo): int
{
    $categoryId = max(0, (int)($_POST['category_id'] ?? 0));
    if ($categoryId <= 0) {
        throw new InvalidArgumentException('Invalid category.');
    }

    $isActive = (int)($_POST['is_active'] ?? 0) === 1 ? 1 : 0;
    $statement = $pdo->prepare('UPDATE categories SET is_active = :is_active, updated_at = NOW() WHERE id = :id');
    $statement->execute([
        ':is_active' => $isActive,
        ':id' => $categoryId,
    ]);

    return $categoryId;
}

function deleteCategoryRecord(PDO $pdo): int
{
    $categoryId = max(0, (int)($_POST['category_id'] ?? 0));
    if ($categoryId <= 0) {
        throw new InvalidArgumentException('Invalid category.');
    }

    $existing = fetchCategoryById($pdo, $categoryId);
    if ($existing === null) {
        throw new RuntimeException('Category not found.');
    }

    $statement = $pdo->prepare('DELETE FROM categories WHERE id = :id');
    $statement->execute([':id' => $categoryId]);

    return $categoryId;
}

function fetchCategoryStats(PDO $pdo): array
{
    $cacheKey = admin_cache_key('categories_stats', ['v' => 1]);
    $cached = null;
    if (admin_cache_fetch($cacheKey, $cached) && is_array($cached)) {
        return array_merge([
            'total_categories' => 0,
            'active_categories' => 0,
            'inactive_categories' => 0,
            'created_this_month' => 0,
            'top_category_name' => 'â€”',
            'top_category_products' => 0,
        ], $cached);
    }

    $stats = [
        'total_categories' => 0,
        'active_categories' => 0,
        'inactive_categories' => 0,
        'created_this_month' => 0,
        'top_category_name' => '—',
        'top_category_products' => 0,
    ];

    if (!admin_table_exists($pdo, 'categories')) {
        return $stats;
    }

    $row = $pdo->query(
        "SELECT
            COUNT(*) AS total_categories,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_categories,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive_categories,
            SUM(CASE WHEN created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 ELSE 0 END) AS created_this_month
         FROM categories"
    )->fetch();

    if (is_array($row)) {
        $stats['total_categories'] = (int)($row['total_categories'] ?? 0);
        $stats['active_categories'] = (int)($row['active_categories'] ?? 0);
        $stats['inactive_categories'] = (int)($row['inactive_categories'] ?? 0);
        $stats['created_this_month'] = (int)($row['created_this_month'] ?? 0);
    }

    if (admin_table_exists($pdo, 'products')) {
        $topRow = $pdo->query(
            "SELECT c.name, COUNT(p.id) AS product_count
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id
             GROUP BY c.id, c.name
             ORDER BY product_count DESC, c.name ASC
             LIMIT 1"
        )->fetch();

        if (is_array($topRow)) {
            $stats['top_category_name'] = (string)($topRow['name'] ?? '—');
            $stats['top_category_products'] = (int)($topRow['product_count'] ?? 0);
        }
    }

    admin_cache_store($cacheKey, $stats, ADMIN_PAGE_CACHE_TTL_SECONDS);
    return $stats;
}

function fetchCategoryList(PDO $pdo, array $filters): array
{
    $cacheKey = admin_cache_key('categories_list', [
        'filters' => $filters,
        'v' => 1,
    ]);
    $cached = null;
    if (admin_cache_fetch($cacheKey, $cached) && is_array($cached)) {
        $cachedRows = $cached['rows'] ?? null;
        $cachedTotal = $cached['total'] ?? null;
        if (is_array($cachedRows)) {
            return ['rows' => $cachedRows, 'total' => (int)$cachedTotal];
        }
    }

    if (!admin_table_exists($pdo, 'categories')) {
        return ['rows' => [], 'total' => 0];
    }

    $productsTableExists = admin_table_exists($pdo, 'products');

    $params = [];
    $where = ['1=1'];

    if ($filters['q'] !== '') {
        $where[] = '(c.name LIKE :q OR c.slug LIKE :q OR c.description LIKE :q)';
        $params[':q'] = '%' . $filters['q'] . '%';
    }

    if ($filters['status'] === 'active') {
        $where[] = 'c.is_active = 1';
    } elseif ($filters['status'] === 'inactive') {
        $where[] = 'c.is_active = 0';
    }

    if ((int)$filters['parent_id'] > 0) {
        $where[] = 'c.parent_id = :parent_id';
        $params[':parent_id'] = (int)$filters['parent_id'];
    }

    $whereSql = implode(' AND ', $where);

    $countSql = "SELECT COUNT(*) FROM categories c WHERE {$whereSql}";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $offset = max(0, ((int)$filters['page'] - 1) * (int)$filters['per_page']);

    $productJoinSql = $productsTableExists
        ? "LEFT JOIN (
            SELECT category_id, COUNT(*) AS items
            FROM products
            GROUP BY category_id
        ) product_count ON product_count.category_id = c.id"
        : "LEFT JOIN (
            SELECT NULL AS category_id, 0 AS items
        ) product_count ON 1 = 0";

    $sql = "
        SELECT
            c.id,
            c.parent_id,
            c.name,
            c.slug,
            c.description,
            c.image,
            c.is_active,
            c.sort_order,
            parent.name AS parent_name,
            COALESCE(product_count.items, 0) AS product_count
        FROM categories c
        LEFT JOIN categories parent ON parent.id = c.parent_id
        {$productJoinSql}
        WHERE {$whereSql}
        ORDER BY
            CASE WHEN c.parent_id IS NULL THEN c.id ELSE c.parent_id END ASC,
            c.parent_id IS NULL DESC,
            c.sort_order ASC,
            c.name ASC
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
    $result = ['rows' => is_array($rows) ? $rows : [], 'total' => $total];
    admin_cache_store($cacheKey, $result, 15);
    return $result;
}

function fetchCategoryById(PDO $pdo, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    $cacheKey = admin_cache_key('categories_detail', [
        'category_id' => $id,
        'v' => 1,
    ]);
    $cached = null;
    if (admin_cache_fetch($cacheKey, $cached)) {
        return is_array($cached) ? $cached : null;
    }

    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    $result = is_array($row) ? $row : null;
    admin_cache_store($cacheKey, $result, ADMIN_PAGE_CACHE_TTL_SECONDS);
    return $result;
}

function fetchParentCategoryOptions(PDO $pdo, ?int $excludeId): array
{
    if (!admin_table_exists($pdo, 'categories')) {
        return [];
    }

    $sql = 'SELECT id, name FROM categories';
    if ($excludeId !== null && $excludeId > 0) {
        $sql .= ' WHERE id <> :exclude_id';
    }
    $sql .= ' ORDER BY name ASC';

    $stmt = $pdo->prepare($sql);
    if ($excludeId !== null && $excludeId > 0) {
        $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll();
    return is_array($rows) ? $rows : [];
}

function isDescendantCategory(PDO $pdo, int $candidateParentId, int $categoryId): bool
{
    $cursor = $candidateParentId;
    $safetyLimit = 100;

    while ($cursor > 0 && $safetyLimit > 0) {
        if ($cursor === $categoryId) {
            return true;
        }

        $stmt = $pdo->prepare('SELECT parent_id FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $cursor]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            break;
        }

        $cursor = (int)($row['parent_id'] ?? 0);
        $safetyLimit--;
    }

    return false;
}

function buildUniqueCategorySlug(PDO $pdo, string $text, ?int $excludeId): string
{
    $base = slugifyCategory($text);
    if ($base === '') {
        $base = 'category';
    }

    $slug = $base;
    $suffix = 2;
    while (categorySlugExists($pdo, $slug, $excludeId)) {
        $slug = $base . '-' . $suffix;
        $suffix++;
    }

    return $slug;
}

function categorySlugExists(PDO $pdo, string $slug, ?int $excludeId): bool
{
    $sql = 'SELECT COUNT(*) FROM categories WHERE slug = :slug';
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

function slugifyCategory(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

function normalizeCategoryParentId(mixed $value): ?int
{
    $id = (int)$value;
    return $id > 0 ? $id : null;
}

function nullIfEmptyCategory(string $value): ?string
{
    $trimmed = trim($value);
    return $trimmed === '' ? null : $trimmed;
}

function setCategoryFlash(string $type, string $message): void
{
    $_SESSION['admin_categories_flash'] = ['type' => $type, 'message' => $message];
}

function pullCategoryFlash(): ?array
{
    if (!isset($_SESSION['admin_categories_flash']) || !is_array($_SESSION['admin_categories_flash'])) {
        return null;
    }
    $flash = $_SESSION['admin_categories_flash'];
    unset($_SESSION['admin_categories_flash']);
    return $flash;
}

function redirectManageCategories(string $returnQuery): never
{
    $safeQuery = sanitizeCategoryReturnQuery($returnQuery);
    header('Location: manageCategories.php' . $safeQuery);
    exit;
}

function sanitizeCategoryReturnQuery(string $query): string
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

function buildCategoryFilterQuery(array $filters): string
{
    $query = http_build_query([
        'q' => (string)$filters['q'],
        'status' => (string)$filters['status'],
        'parent_id' => (int)$filters['parent_id'],
        'per_page' => (int)$filters['per_page'],
        'page' => (int)$filters['page'],
    ]);
    return $query === '' ? '' : '?' . $query;
}



