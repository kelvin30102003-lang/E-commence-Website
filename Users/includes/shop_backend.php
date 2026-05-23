<?php

declare(strict_types=1);

require_once __DIR__ . '/../../DB/railway_mysql.php';

if (!defined('SHOP_CART_SESSION_KEY')) {
    define('SHOP_CART_SESSION_KEY', 'luvshop_cart');
}
if (!defined('SHOP_CART_FLASH_KEY')) {
    define('SHOP_CART_FLASH_KEY', 'luvshop_cart_flash');
}

function shop_db(): PDO
{
    return railway_mysql_db();
}

function shop_table_exists(PDO $pdo, string $tableName): bool
{
    static $cache = [];
    $key = strtolower(trim($tableName));
    if ($key === '') {
        return false;
    }

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $statement = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name'
    );
    $statement->execute([':table_name' => $tableName]);
    $exists = ((int)$statement->fetchColumn()) > 0;
    $cache[$key] = $exists;
    return $exists;
}

function shop_table_has_column(PDO $pdo, string $tableName, string $columnName): bool
{
    static $cache = [];
    $table = strtolower(trim($tableName));
    $column = strtolower(trim($columnName));
    if ($table === '' || $column === '') {
        return false;
    }

    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    if (!shop_table_exists($pdo, $tableName)) {
        $cache[$key] = false;
        return false;
    }

    $statement = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table_name AND column_name = :column_name'
    );
    $statement->execute([
        ':table_name' => $tableName,
        ':column_name' => $columnName,
    ]);
    $exists = ((int)$statement->fetchColumn()) > 0;
    $cache[$key] = $exists;
    return $exists;
}

function shop_normalize_filters(array $input): array
{
    $sort = trim((string)($input['sort'] ?? 'newest'));
    $allowedSorts = ['newest', 'price_low', 'price_high', 'name_az'];
    if (!in_array($sort, $allowedSorts, true)) {
        $sort = 'newest';
    }

    return [
        'q' => trim((string)($input['q'] ?? '')),
        'category' => trim((string)($input['category'] ?? '')),
        'sort' => $sort,
        'page' => max(1, (int)($input['page'] ?? 1)),
        'per_page' => max(8, min(48, (int)($input['per_page'] ?? 12))),
    ];
}

function shop_fetch_categories(PDO $pdo): array
{
    if (!shop_table_exists($pdo, 'categories') || !shop_table_exists($pdo, 'products')) {
        return [];
    }

    $sql = "
        SELECT
            c.id,
            c.name,
            c.slug,
            COALESCE(COUNT(DISTINCT p.id), 0) AS product_count
        FROM categories c
        LEFT JOIN products p
            ON p.category_id = c.id
           AND p.status = 'active'
        WHERE c.is_active = 1
        GROUP BY c.id, c.name, c.slug
        ORDER BY c.sort_order ASC, c.name ASC
    ";

    $rows = $pdo->query($sql)->fetchAll();
    return is_array($rows) ? $rows : [];
}

function shop_fetch_products(PDO $pdo, array $filters): array
{
    if (!shop_table_exists($pdo, 'products')) {
        return ['rows' => [], 'total' => 0];
    }

    $hasCategories = shop_table_exists($pdo, 'categories');
    $hasBrands = shop_table_exists($pdo, 'brands');
    $hasVariants = shop_table_exists($pdo, 'product_variants');
    $hasImages = shop_table_exists($pdo, 'product_images');

    $params = [];
    $where = ["p.status = 'active'"];

    if ($filters['q'] !== '') {
        $searchValue = '%' . $filters['q'] . '%';
        $where[] = '(p.name LIKE :q_name OR p.short_description LIKE :q_short OR p.slug LIKE :q_slug OR c.name LIKE :q_category OR b.name LIKE :q_brand)';
        $params[':q_name'] = $searchValue;
        $params[':q_short'] = $searchValue;
        $params[':q_slug'] = $searchValue;
        $params[':q_category'] = $searchValue;
        $params[':q_brand'] = $searchValue;
    }

    if ($filters['category'] !== '') {
        $where[] = 'c.slug = :category_slug';
        $params[':category_slug'] = $filters['category'];
    }

    if ($hasCategories) {
        $where[] = '(c.id IS NULL OR c.is_active = 1)';
    }
    if ($hasBrands) {
        $where[] = '(b.id IS NULL OR b.is_active = 1)';
    }

    $whereSql = implode(' AND ', $where);

    $categoryJoin = $hasCategories
        ? 'LEFT JOIN categories c ON c.id = p.category_id'
        : 'LEFT JOIN (SELECT NULL AS id, NULL AS name, NULL AS slug, 1 AS is_active) c ON 1 = 0';
    $brandJoin = $hasBrands
        ? 'LEFT JOIN brands b ON b.id = p.brand_id'
        : 'LEFT JOIN (SELECT NULL AS id, NULL AS name, 1 AS is_active) b ON 1 = 0';
    $variantJoin = $hasVariants
        ? "LEFT JOIN (
                SELECT
                    product_id,
                    MIN(price) AS min_price,
                    MAX(compare_price) AS compare_price,
                    SUM(stock_quantity) AS stock_quantity
                FROM product_variants
                WHERE is_active = 1
                GROUP BY product_id
            ) pv ON pv.product_id = p.id"
        : 'LEFT JOIN (SELECT NULL AS product_id, 0 AS min_price, NULL AS compare_price, 0 AS stock_quantity) pv ON 1 = 0';
    $imageJoin = $hasImages
        ? "LEFT JOIN (
                SELECT product_id, MIN(image) AS image
                FROM product_images
                GROUP BY product_id
            ) pi ON pi.product_id = p.id"
        : 'LEFT JOIN (SELECT NULL AS product_id, NULL AS image) pi ON 1 = 0';

    $countSql = "
        SELECT COUNT(*)
        FROM products p
        {$categoryJoin}
        {$brandJoin}
        {$variantJoin}
        {$imageJoin}
        WHERE {$whereSql}
    ";
    $countStatement = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStatement->bindValue($key, $value);
    }
    $countStatement->execute();
    $total = (int)$countStatement->fetchColumn();

    $orderBy = match ($filters['sort']) {
        'price_low' => 'COALESCE(pv.min_price, 0) ASC, p.updated_at DESC, p.id DESC',
        'price_high' => 'COALESCE(pv.min_price, 0) DESC, p.updated_at DESC, p.id DESC',
        'name_az' => 'p.name ASC, p.id DESC',
        default => 'p.updated_at DESC, p.id DESC',
    };

    $offset = max(0, ($filters['page'] - 1) * $filters['per_page']);

    $sql = "
        SELECT
            p.id,
            p.name,
            p.slug,
            p.short_description,
            p.description,
            COALESCE(c.name, '') AS category_name,
            COALESCE(c.slug, '') AS category_slug,
            COALESCE(c.id, 0) AS category_id,
            COALESCE(b.name, '') AS brand_name,
            COALESCE(pi.image, '') AS image,
            COALESCE(pv.min_price, 0) AS price,
            pv.compare_price,
            COALESCE(pv.stock_quantity, 0) AS stock_quantity
        FROM products p
        {$categoryJoin}
        {$brandJoin}
        {$variantJoin}
        {$imageJoin}
        WHERE {$whereSql}
        ORDER BY {$orderBy}
        LIMIT :limit OFFSET :offset
    ";

    $statement = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $statement->bindValue($key, $value);
    }
    $statement->bindValue(':limit', $filters['per_page'], PDO::PARAM_INT);
    $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $statement->execute();
    $rows = $statement->fetchAll();
    $rows = is_array($rows) ? $rows : [];
    shop_hydrate_products_with_images($pdo, $rows);

    return [
        'rows' => $rows,
        'total' => $total,
    ];
}

function shop_hydrate_products_with_images(PDO $pdo, array &$rows, int $maxImagesPerProduct = 8): void
{
    if (count($rows) === 0) {
        return;
    }

    $maxImagesPerProduct = max(1, min(20, $maxImagesPerProduct));

    foreach ($rows as &$row) {
        $primary = trim((string)($row['image'] ?? ''));
        $row['images'] = $primary !== '' ? [$primary] : [];
    }
    unset($row);

    if (!shop_table_exists($pdo, 'product_images')) {
        return;
    }

    $productIds = array_values(array_unique(array_map(
        static fn (array $row): int => (int)($row['id'] ?? 0),
        $rows
    )));
    $productIds = array_values(array_filter($productIds, static fn (int $id): bool => $id > 0));

    if (count($productIds) === 0) {
        return;
    }

    $placeholders = implode(', ', array_fill(0, count($productIds), '?'));
    $statement = $pdo->prepare(
        "SELECT product_id, image
         FROM product_images
         WHERE product_id IN ({$placeholders})
           AND image IS NOT NULL
           AND image <> ''
         ORDER BY product_id ASC, id ASC"
    );
    foreach ($productIds as $index => $productId) {
        $statement->bindValue($index + 1, $productId, PDO::PARAM_INT);
    }
    $statement->execute();
    $imageRows = $statement->fetchAll();

    $imagesByProductId = [];
    if (is_array($imageRows)) {
        foreach ($imageRows as $imageRow) {
            $productId = (int)($imageRow['product_id'] ?? 0);
            $image = trim((string)($imageRow['image'] ?? ''));
            if ($productId <= 0 || $image === '') {
                continue;
            }
            if (!isset($imagesByProductId[$productId])) {
                $imagesByProductId[$productId] = [];
            }
            $imagesByProductId[$productId][] = $image;
        }
    }

    foreach ($rows as &$row) {
        $productId = (int)($row['id'] ?? 0);
        $gallery = is_array($row['images'] ?? null) ? $row['images'] : [];
        if (isset($imagesByProductId[$productId])) {
            $gallery = array_merge($gallery, $imagesByProductId[$productId]);
        }
        $gallery = array_values(array_unique(array_filter($gallery, static fn (string $src): bool => trim($src) !== '')));
        if (count($gallery) > $maxImagesPerProduct) {
            $gallery = array_slice($gallery, 0, $maxImagesPerProduct);
        }
        $row['images'] = $gallery;
    }
    unset($row);
}

function shop_featured_products(PDO $pdo, int $limit = 8): array
{
    $limit = max(1, min($limit, 20));
    $filters = [
        'q' => '',
        'category' => '',
        'sort' => 'newest',
        'page' => 1,
        'per_page' => $limit,
    ];
    return shop_fetch_products($pdo, $filters)['rows'];
}

function shop_product_detail_url(array $product): string
{
    $slug = trim((string)($product['slug'] ?? ''));
    if ($slug !== '') {
        return 'productDetail.php?slug=' . rawurlencode($slug);
    }

    $id = (int)($product['id'] ?? 0);
    if ($id > 0) {
        return 'productDetail.php?id=' . $id;
    }

    return 'shop.php';
}

function shop_fetch_product_detail(PDO $pdo, string $slug = '', int $id = 0): ?array
{
    if (!shop_table_exists($pdo, 'products')) {
        return null;
    }

    $slug = trim($slug);
    $id = max(0, $id);
    if ($slug === '' && $id <= 0) {
        return null;
    }

    $hasCategories = shop_table_exists($pdo, 'categories');
    $hasBrands = shop_table_exists($pdo, 'brands');
    $hasVariants = shop_table_exists($pdo, 'product_variants');
    $hasImages = shop_table_exists($pdo, 'product_images');

    $categoryJoin = $hasCategories
        ? 'LEFT JOIN categories c ON c.id = p.category_id'
        : 'LEFT JOIN (SELECT NULL AS id, NULL AS name, NULL AS slug, 1 AS is_active) c ON 1 = 0';
    $brandJoin = $hasBrands
        ? 'LEFT JOIN brands b ON b.id = p.brand_id'
        : 'LEFT JOIN (SELECT NULL AS id, NULL AS name, 1 AS is_active) b ON 1 = 0';
    $variantJoin = $hasVariants
        ? "LEFT JOIN (
                SELECT
                    product_id,
                    MIN(price) AS min_price,
                    MAX(compare_price) AS compare_price,
                    SUM(stock_quantity) AS stock_quantity
                FROM product_variants
                WHERE is_active = 1
                GROUP BY product_id
            ) pv ON pv.product_id = p.id"
        : 'LEFT JOIN (SELECT NULL AS product_id, 0 AS min_price, NULL AS compare_price, 0 AS stock_quantity) pv ON 1 = 0';
    $imageJoin = $hasImages
        ? "LEFT JOIN (
                SELECT product_id, MIN(image) AS image
                FROM product_images
                GROUP BY product_id
            ) pi ON pi.product_id = p.id"
        : 'LEFT JOIN (SELECT NULL AS product_id, NULL AS image) pi ON 1 = 0';

    $where = ["p.status = 'active'"];
    $params = [];

    if ($slug !== '') {
        $where[] = 'p.slug = :slug';
        $params[':slug'] = $slug;
    } else {
        $where[] = 'p.id = :id';
        $params[':id'] = $id;
    }

    if ($hasCategories) {
        $where[] = '(c.id IS NULL OR c.is_active = 1)';
    }
    if ($hasBrands) {
        $where[] = '(b.id IS NULL OR b.is_active = 1)';
    }

    $sql = "
        SELECT
            p.id,
            p.name,
            p.slug,
            p.short_description,
            p.description,
            p.status,
            COALESCE(c.id, 0) AS category_id,
            COALESCE(c.name, '') AS category_name,
            COALESCE(c.slug, '') AS category_slug,
            COALESCE(b.id, 0) AS brand_id,
            COALESCE(b.name, '') AS brand_name,
            COALESCE(pi.image, '') AS image,
            COALESCE(pv.min_price, 0) AS price,
            pv.compare_price,
            COALESCE(pv.stock_quantity, 0) AS stock_quantity
        FROM products p
        {$categoryJoin}
        {$brandJoin}
        {$variantJoin}
        {$imageJoin}
        WHERE " . implode(' AND ', $where) . "
        ORDER BY p.id DESC
        LIMIT 1
    ";

    $statement = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        if ($key === ':id') {
            $statement->bindValue($key, (int)$value, PDO::PARAM_INT);
        } else {
            $statement->bindValue($key, (string)$value);
        }
    }
    $statement->execute();
    $row = $statement->fetch();
    if (!is_array($row)) {
        return null;
    }

    $productId = (int)$row['id'];
    $primaryImage = trim((string)($row['image'] ?? ''));
    $row['images'] = shop_fetch_product_images($pdo, $productId, $primaryImage);
    $row['variants'] = shop_fetch_product_variants($pdo, $productId);

    return $row;
}

function shop_fetch_product_images(PDO $pdo, int $productId, string $primaryImage = ''): array
{
    $images = [];

    if (shop_table_exists($pdo, 'product_images')) {
        $statement = $pdo->prepare(
            'SELECT image FROM product_images WHERE product_id = :product_id AND image IS NOT NULL AND image <> "" ORDER BY id ASC'
        );
        $statement->execute([':product_id' => $productId]);
        $rows = $statement->fetchAll();
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $image = trim((string)($row['image'] ?? ''));
                if ($image !== '') {
                    $images[] = $image;
                }
            }
        }
    }

    $primaryImage = trim($primaryImage);
    if ($primaryImage !== '') {
        array_unshift($images, $primaryImage);
    }

    $images = array_values(array_unique($images));
    return $images;
}

function shop_fetch_product_variants(PDO $pdo, int $productId): array
{
    if (!shop_table_exists($pdo, 'product_variants')) {
        return [];
    }

    $statement = $pdo->prepare(
        "SELECT
            id,
            sku,
            variant_name,
            price,
            compare_price,
            stock_quantity,
            low_stock_threshold,
            is_active
         FROM product_variants
         WHERE product_id = :product_id AND is_active = 1
         ORDER BY price ASC, id ASC"
    );
    $statement->execute([':product_id' => $productId]);
    $rows = $statement->fetchAll();
    return is_array($rows) ? $rows : [];
}

function shop_fetch_related_products(PDO $pdo, int $productId, int $categoryId, int $limit = 4): array
{
    if (!shop_table_exists($pdo, 'products')) {
        return [];
    }

    $limit = max(1, min($limit, 12));
    $hasCategories = shop_table_exists($pdo, 'categories');
    $hasBrands = shop_table_exists($pdo, 'brands');
    $hasVariants = shop_table_exists($pdo, 'product_variants');
    $hasImages = shop_table_exists($pdo, 'product_images');

    $categoryJoin = $hasCategories
        ? 'LEFT JOIN categories c ON c.id = p.category_id'
        : 'LEFT JOIN (SELECT NULL AS id, NULL AS name, NULL AS slug, 1 AS is_active) c ON 1 = 0';
    $brandJoin = $hasBrands
        ? 'LEFT JOIN brands b ON b.id = p.brand_id'
        : 'LEFT JOIN (SELECT NULL AS id, NULL AS name, 1 AS is_active) b ON 1 = 0';
    $variantJoin = $hasVariants
        ? "LEFT JOIN (
                SELECT
                    product_id,
                    MIN(price) AS min_price,
                    MAX(compare_price) AS compare_price,
                    SUM(stock_quantity) AS stock_quantity
                FROM product_variants
                WHERE is_active = 1
                GROUP BY product_id
            ) pv ON pv.product_id = p.id"
        : 'LEFT JOIN (SELECT NULL AS product_id, 0 AS min_price, NULL AS compare_price, 0 AS stock_quantity) pv ON 1 = 0';
    $imageJoin = $hasImages
        ? "LEFT JOIN (
                SELECT product_id, MIN(image) AS image
                FROM product_images
                GROUP BY product_id
            ) pi ON pi.product_id = p.id"
        : 'LEFT JOIN (SELECT NULL AS product_id, NULL AS image) pi ON 1 = 0';

    $sql = "
        SELECT
            p.id,
            p.name,
            p.slug,
            p.short_description,
            COALESCE(c.name, '') AS category_name,
            COALESCE(c.slug, '') AS category_slug,
            COALESCE(c.id, 0) AS category_id,
            COALESCE(b.name, '') AS brand_name,
            COALESCE(pi.image, '') AS image,
            COALESCE(pv.min_price, 0) AS price,
            pv.compare_price,
            COALESCE(pv.stock_quantity, 0) AS stock_quantity
        FROM products p
        {$categoryJoin}
        {$brandJoin}
        {$variantJoin}
        {$imageJoin}
        WHERE p.status = 'active'
          AND p.id <> :product_id
        ORDER BY
          CASE WHEN :category_id_check > 0 AND p.category_id = :category_id_match THEN 1 ELSE 0 END DESC,
          p.updated_at DESC,
          p.id DESC
        LIMIT :limit
    ";

    $statement = $pdo->prepare($sql);
    $statement->bindValue(':product_id', $productId, PDO::PARAM_INT);
    $statement->bindValue(':category_id_check', $categoryId, PDO::PARAM_INT);
    $statement->bindValue(':category_id_match', $categoryId, PDO::PARAM_INT);
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();
    $rows = $statement->fetchAll();
    return is_array($rows) ? $rows : [];
}

function shop_ensure_contact_tables(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS contact_settings (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            support_email VARCHAR(255) NOT NULL,
            support_phone VARCHAR(80) NOT NULL,
            support_address VARCHAR(255) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_contact_settings_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS contact_messages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(150) NOT NULL,
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('new','read','resolved') NOT NULL DEFAULT 'new',
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_contact_messages_status (status),
            KEY idx_contact_messages_created_at (created_at),
            KEY idx_contact_messages_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function shop_fetch_contact_settings(PDO $pdo): array
{
    shop_ensure_contact_tables($pdo);

    $statement = $pdo->query(
        'SELECT support_email, support_phone, support_address FROM contact_settings WHERE is_active = 1 ORDER BY id DESC LIMIT 1'
    );
    $row = $statement->fetch();

    if (!is_array($row)) {
        $insert = $pdo->prepare(
            'INSERT INTO contact_settings (support_email, support_phone, support_address, is_active) VALUES (:support_email, :support_phone, :support_address, 1)'
        );
        $insert->execute([
            ':support_email' => 'hello@luvshop.com',
            ':support_phone' => '+95 786917400',
            ':support_address' => '145/4, Yangon',
        ]);

        $statement = $pdo->query(
            'SELECT support_email, support_phone, support_address FROM contact_settings WHERE is_active = 1 ORDER BY id DESC LIMIT 1'
        );
        $row = $statement->fetch();
    }

    return [
        'support_email' => trim((string)($row['support_email'] ?? 'hello@luvshop.com')),
        'support_phone' => trim((string)($row['support_phone'] ?? '+95 786917400')),
        'support_address' => trim((string)($row['support_address'] ?? '145/4, Yangon')),
    ];
}

function shop_save_contact_message(PDO $pdo, array $payload): int
{
    shop_ensure_contact_tables($pdo);

    $fullName = trim((string)($payload['full_name'] ?? ''));
    $email = strtolower(trim((string)($payload['email'] ?? '')));
    $subject = trim((string)($payload['subject'] ?? ''));
    $message = trim((string)($payload['message'] ?? ''));

    if ($fullName === '' || $email === '' || $subject === '' || $message === '') {
        throw new InvalidArgumentException('Please fill all contact form fields.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Please enter a valid email address.');
    }
    if (strlen($fullName) > 150) {
        throw new InvalidArgumentException('Name is too long.');
    }
    if (strlen($email) > 255) {
        throw new InvalidArgumentException('Email is too long.');
    }
    if (strlen($subject) > 200) {
        throw new InvalidArgumentException('Subject is too long.');
    }
    if (strlen($message) > 5000) {
        throw new InvalidArgumentException('Message is too long.');
    }

    $ipAddress = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    $userAgent = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if ($ipAddress === '') {
        $ipAddress = null;
    }
    if ($userAgent === '') {
        $userAgent = null;
    } elseif (strlen($userAgent) > 255) {
        $userAgent = substr($userAgent, 0, 255);
    }

    $statement = $pdo->prepare(
        'INSERT INTO contact_messages (full_name, email, subject, message, ip_address, user_agent) VALUES (:full_name, :email, :subject, :message, :ip_address, :user_agent)'
    );
    $statement->execute([
        ':full_name' => $fullName,
        ':email' => $email,
        ':subject' => $subject,
        ':message' => $message,
        ':ip_address' => $ipAddress,
        ':user_agent' => $userAgent,
    ]);

    return (int)$pdo->lastInsertId();
}

function shop_h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function shop_money(float $amount): string
{
    return '$' . number_format($amount, 2);
}

function shop_placeholder_gradient(int $index): string
{
    $gradients = [
        'from-rose-100 to-pink-200',
        'from-amber-100 to-yellow-200',
        'from-teal-100 to-emerald-200',
        'from-sky-100 to-blue-200',
    ];
    return $gradients[$index % count($gradients)];
}

function shop_short_text(string $value, int $length = 86): string
{
    $value = trim($value);
    if ($value === '' || strlen($value) <= $length) {
        return $value;
    }
    return substr($value, 0, $length - 3) . '...';
}

function shop_start_session(): void
{
    static $attempted = false;

    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    if ($attempted) {
        return;
    }
    $attempted = true;

    if (headers_sent()) {
        return;
    }

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);

    @session_start([
        'cookie_httponly' => true,
        'cookie_secure' => $isSecure,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

function shop_cart_line_key(int $productId, int $variantId): string
{
    return 'p' . $productId . ':v' . max(0, $variantId);
}

function shop_cart_storage(): array
{
    shop_start_session();

    $raw = $_SESSION[SHOP_CART_SESSION_KEY] ?? [];
    if (!is_array($raw)) {
        return [];
    }

    $normalized = [];
    foreach ($raw as $item) {
        if (!is_array($item)) {
            continue;
        }

        $productId = max(0, (int)($item['product_id'] ?? 0));
        $variantId = max(0, (int)($item['variant_id'] ?? 0));
        $quantity = max(1, (int)($item['quantity'] ?? 1));
        if ($productId <= 0) {
            continue;
        }

        $lineKey = shop_cart_line_key($productId, $variantId);
        $normalized[$lineKey] = [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'added_at' => (string)($item['added_at'] ?? gmdate('c')),
        ];
    }

    return $normalized;
}

function shop_cart_save_storage(array $cart): void
{
    shop_start_session();
    $_SESSION[SHOP_CART_SESSION_KEY] = $cart;
}

function shop_cart_set_flash(array $payload): void
{
    shop_start_session();
    $_SESSION[SHOP_CART_FLASH_KEY] = $payload;
}

function shop_cart_pull_flash(): ?array
{
    shop_start_session();
    if (!isset($_SESSION[SHOP_CART_FLASH_KEY]) || !is_array($_SESSION[SHOP_CART_FLASH_KEY])) {
        return null;
    }

    $flash = $_SESSION[SHOP_CART_FLASH_KEY];
    unset($_SESSION[SHOP_CART_FLASH_KEY]);
    return $flash;
}

function shop_cart_item_count(): int
{
    $cart = shop_cart_storage();
    $count = 0;
    foreach ($cart as $item) {
        $count += max(0, (int)($item['quantity'] ?? 0));
    }
    return $count;
}

function shop_find_product_variant(array $product, int $variantId): ?array
{
    $variants = is_array($product['variants'] ?? null) ? $product['variants'] : [];
    if (count($variants) === 0) {
        return null;
    }

    if ($variantId > 0) {
        foreach ($variants as $variant) {
            if ((int)($variant['id'] ?? 0) === $variantId) {
                return is_array($variant) ? $variant : null;
            }
        }
    }

    $first = $variants[0] ?? null;
    return is_array($first) ? $first : null;
}

function shop_fetch_cart_candidate(PDO $pdo, int $productId, int $variantId = 0): array
{
    if ($productId <= 0) {
        throw new InvalidArgumentException('Invalid product.');
    }

    $product = shop_fetch_product_detail($pdo, '', $productId);
    if (!is_array($product)) {
        throw new RuntimeException('Product is unavailable.');
    }

    $variant = shop_find_product_variant($product, $variantId);
    $selectedVariantId = $variant !== null ? (int)($variant['id'] ?? 0) : 0;
    $variantName = $variant !== null ? trim((string)($variant['variant_name'] ?? '')) : '';
    $sku = $variant !== null ? trim((string)($variant['sku'] ?? '')) : '';

    $stock = $variant !== null
        ? max(0, (int)($variant['stock_quantity'] ?? 0))
        : max(0, (int)($product['stock_quantity'] ?? 0));
    if ($stock <= 0) {
        throw new RuntimeException('This product is out of stock.');
    }

    $unitPrice = $variant !== null
        ? max(0.0, (float)($variant['price'] ?? 0))
        : max(0.0, (float)($product['price'] ?? 0));

    $images = is_array($product['images'] ?? null) ? $product['images'] : [];
    $image = '';
    if (count($images) > 0) {
        $image = trim((string)$images[0]);
    }

    return [
        'product_id' => (int)$product['id'],
        'variant_id' => $selectedVariantId,
        'product_name' => trim((string)($product['name'] ?? 'Product')),
        'variant_name' => $variantName,
        'sku' => $sku,
        'unit_price' => $unitPrice,
        'stock_quantity' => $stock,
        'image' => $image,
        'detail_url' => shop_product_detail_url($product),
    ];
}

function shop_cart_add_item(PDO $pdo, int $productId, int $quantity = 1, int $variantId = 0): array
{
    $quantity = max(1, min(99, $quantity));
    $candidate = shop_fetch_cart_candidate($pdo, $productId, $variantId);

    $lineKey = shop_cart_line_key((int)$candidate['product_id'], (int)$candidate['variant_id']);
    $cart = shop_cart_storage();
    $existingQty = isset($cart[$lineKey]) ? max(0, (int)$cart[$lineKey]['quantity']) : 0;

    $maxStock = max(1, (int)$candidate['stock_quantity']);
    $nextQty = min($maxStock, $existingQty + $quantity);
    if ($nextQty <= 0) {
        throw new RuntimeException('Unable to add this product to cart.');
    }

    $cart[$lineKey] = [
        'product_id' => (int)$candidate['product_id'],
        'variant_id' => (int)$candidate['variant_id'],
        'quantity' => $nextQty,
        'added_at' => gmdate('c'),
    ];
    shop_cart_save_storage($cart);

    return [
        'line_key' => $lineKey,
        'quantity' => $nextQty,
        'product_id' => (int)$candidate['product_id'],
        'variant_id' => (int)$candidate['variant_id'],
        'product_name' => (string)$candidate['product_name'],
        'variant_name' => (string)$candidate['variant_name'],
        'unit_price' => (float)$candidate['unit_price'],
        'image' => (string)$candidate['image'],
        'detail_url' => (string)$candidate['detail_url'],
        'cart_count' => shop_cart_item_count(),
    ];
}

function shop_cart_set_quantity(PDO $pdo, string $lineKey, int $quantity): void
{
    $cart = shop_cart_storage();
    if (!isset($cart[$lineKey]) || !is_array($cart[$lineKey])) {
        return;
    }

    $line = $cart[$lineKey];
    $productId = max(0, (int)($line['product_id'] ?? 0));
    $variantId = max(0, (int)($line['variant_id'] ?? 0));
    if ($productId <= 0) {
        unset($cart[$lineKey]);
        shop_cart_save_storage($cart);
        return;
    }

    if ($quantity <= 0) {
        unset($cart[$lineKey]);
        shop_cart_save_storage($cart);
        return;
    }

    $candidate = shop_fetch_cart_candidate($pdo, $productId, $variantId);
    $maxStock = max(1, (int)$candidate['stock_quantity']);
    $cart[$lineKey]['quantity'] = max(1, min(99, min($quantity, $maxStock)));
    shop_cart_save_storage($cart);
}

function shop_cart_remove_line(string $lineKey): void
{
    $cart = shop_cart_storage();
    if (isset($cart[$lineKey])) {
        unset($cart[$lineKey]);
        shop_cart_save_storage($cart);
    }
}

function shop_cart_clear(): void
{
    shop_start_session();
    unset($_SESSION[SHOP_CART_SESSION_KEY]);
}

function shop_cart_details(PDO $pdo): array
{
    $cart = shop_cart_storage();
    $items = [];
    $subtotal = 0.0;
    $quantityTotal = 0;
    $changed = false;

    foreach ($cart as $lineKey => $line) {
        $productId = max(0, (int)($line['product_id'] ?? 0));
        $variantId = max(0, (int)($line['variant_id'] ?? 0));
        $quantity = max(1, (int)($line['quantity'] ?? 1));

        if ($productId <= 0) {
            unset($cart[$lineKey]);
            $changed = true;
            continue;
        }

        try {
            $candidate = shop_fetch_cart_candidate($pdo, $productId, $variantId);
        } catch (Throwable) {
            unset($cart[$lineKey]);
            $changed = true;
            continue;
        }

        $maxStock = max(1, (int)$candidate['stock_quantity']);
        $quantity = min($quantity, $maxStock);
        if ($quantity !== (int)$line['quantity']) {
            $cart[$lineKey]['quantity'] = $quantity;
            $changed = true;
        }

        $unitPrice = (float)$candidate['unit_price'];
        $lineTotal = round($unitPrice * $quantity, 2);
        $subtotal += $lineTotal;
        $quantityTotal += $quantity;

        $items[] = [
            'line_key' => $lineKey,
            'product_id' => (int)$candidate['product_id'],
            'variant_id' => (int)$candidate['variant_id'],
            'product_name' => (string)$candidate['product_name'],
            'variant_name' => (string)$candidate['variant_name'],
            'sku' => (string)$candidate['sku'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
            'stock_quantity' => $maxStock,
            'image' => (string)$candidate['image'],
            'detail_url' => (string)$candidate['detail_url'],
        ];
    }

    if ($changed) {
        shop_cart_save_storage($cart);
    }

    return [
        'items' => $items,
        'subtotal' => round($subtotal, 2),
        'item_count' => $quantityTotal,
        'line_count' => count($items),
    ];
}

function shop_shipping_methods(): array
{
    return [
        'standard' => [
            'label' => 'Standard Delivery',
            'fee' => 3000.0,
            'eta' => '3-5 days',
            'shipment_status' => 'preparing',
        ],
        'express' => [
            'label' => 'Express Delivery',
            'fee' => 7000.0,
            'eta' => 'Next day',
            'shipment_status' => 'shipped',
        ],
        'pickup' => [
            'label' => 'Store Pickup',
            'fee' => 0.0,
            'eta' => 'Same day pickup',
            'shipment_status' => 'preparing',
        ],
    ];
}

function shop_checkout_default_form(): array
{
    return [
        'full_name' => '',
        'email' => '',
        'phone' => '',
        'address_line1' => '',
        'address_line2' => '',
        'city' => '',
        'township' => '',
        'state' => '',
        'country' => 'Myanmar',
        'postal_code' => '',
        'customer_note' => '',
        'payment_method' => 'cod',
        'shipping_method' => 'standard',
    ];
}

function shop_payment_methods(): array
{
    return [
        'cod' => 'Cash on Delivery',
        'prepaid' => 'Prepaid',
    ];
}

function shop_ensure_checkout_tables(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS orders (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            order_number VARCHAR(50) NOT NULL,
            user_id BIGINT UNSIGNED NULL,
            subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            shipping_fee DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            order_status ENUM('pending','confirmed','processing','shipped','delivered','cancelled','refunded') NOT NULL DEFAULT 'pending',
            payment_status ENUM('unpaid','paid','failed','refunded','partial_refund') NOT NULL DEFAULT 'unpaid',
            shipping_status ENUM('not_shipped','preparing','shipped','in_transit','delivered','returned') NOT NULL DEFAULT 'not_shipped',
            payment_method VARCHAR(50) NULL,
            customer_note TEXT NULL,
            placed_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_orders_order_number (order_number),
            KEY idx_orders_user_id (user_id),
            KEY idx_orders_status (order_status),
            KEY idx_orders_payment_status (payment_status),
            KEY idx_orders_shipping_status (shipping_status),
            KEY idx_orders_placed_at (placed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    if (!shop_table_has_column($pdo, 'orders', 'payment_method')) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) NULL AFTER shipping_status");
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS order_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NULL,
            product_variant_id BIGINT UNSIGNED NULL,
            product_name VARCHAR(200) NOT NULL,
            variant_name VARCHAR(200) NULL,
            sku VARCHAR(100) NULL,
            quantity INT UNSIGNED NOT NULL DEFAULT 1,
            unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            total_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_order_items_order_id (order_id),
            KEY idx_order_items_product_id (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS shipments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            courier_name VARCHAR(150) NULL,
            tracking_number VARCHAR(150) NULL,
            shipping_address JSON NOT NULL,
            shipping_fee DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            status ENUM('preparing','shipped','in_transit','delivered','returned','cancelled') NOT NULL DEFAULT 'preparing',
            shipped_at TIMESTAMP NULL DEFAULT NULL,
            delivered_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_shipments_order_id (order_id),
            KEY idx_shipments_status (status),
            KEY idx_shipments_courier_name (courier_name),
            KEY idx_shipments_tracking_number (tracking_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function shop_checkout_validate(array $input): array
{
    $form = shop_checkout_default_form();
    foreach ($form as $key => $defaultValue) {
        if (array_key_exists($key, $input)) {
            $form[$key] = trim((string)$input[$key]);
        }
    }

    $errors = [];
    if ($form['full_name'] === '') {
        $errors[] = 'Full name is required.';
    }
    if ($form['email'] === '' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    if ($form['phone'] === '') {
        $errors[] = 'Phone is required.';
    }
    if ($form['address_line1'] === '') {
        $errors[] = 'Delivery address is required.';
    }
    if ($form['city'] === '') {
        $errors[] = 'City is required.';
    }

    $paymentMethods = shop_payment_methods();
    if (!isset($paymentMethods[$form['payment_method']])) {
        $errors[] = 'Unsupported payment method.';
    }

    $shippingMethods = shop_shipping_methods();
    if (!isset($shippingMethods[$form['shipping_method']])) {
        $errors[] = 'Unsupported shipping method.';
    }

    if (count($errors) > 0) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }

    return $form;
}

function shop_generate_order_number(PDO $pdo): string
{
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $candidate = 'ORD-' . gmdate('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $statement = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE order_number = :order_number');
        $statement->execute([':order_number' => $candidate]);
        if ((int)$statement->fetchColumn() === 0) {
            return $candidate;
        }
    }

    return 'ORD-' . gmdate('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

function shop_encode_shipping_payload(array $payload): string
{
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return is_string($json) ? $json : '{}';
}

function shop_null_if_empty(string $value): ?string
{
    $trimmed = trim($value);
    return $trimmed === '' ? null : $trimmed;
}

function shop_resolve_checkout_user_id(PDO $pdo, array $form): ?int
{
    if (!shop_table_exists($pdo, 'users')) {
        return null;
    }

    $email = strtolower(trim((string)($form['email'] ?? '')));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    $find = $pdo->prepare('SELECT id FROM users WHERE email = :email ORDER BY id ASC LIMIT 1');
    $find->execute([':email' => $email]);
    $existing = $find->fetch();
    if (is_array($existing) && (int)($existing['id'] ?? 0) > 0) {
        return (int)$existing['id'];
    }

    $firebaseUid = 'guest:' . bin2hex(random_bytes(16));
    $insert = $pdo->prepare(
        'INSERT INTO users (firebase_uid, name, email, phone, role, is_active, can_manage_products, can_manage_orders, can_manage_users, can_manage_coupons, can_manage_reports, email_verified, created_at, updated_at)
         VALUES (:firebase_uid, :name, :email, :phone, :role, 1, 0, 0, 0, 0, 0, 0, NOW(), NOW())'
    );

    try {
        $insert->execute([
            ':firebase_uid' => $firebaseUid,
            ':name' => trim((string)($form['full_name'] ?? '')),
            ':email' => $email,
            ':phone' => trim((string)($form['phone'] ?? '')),
            ':role' => 'customer',
        ]);
        return (int)$pdo->lastInsertId();
    } catch (Throwable) {
        $find->execute([':email' => $email]);
        $retry = $find->fetch();
        if (is_array($retry) && (int)($retry['id'] ?? 0) > 0) {
            return (int)$retry['id'];
        }
        return null;
    }
}

function shop_place_order(PDO $pdo, array $formInput): array
{
    shop_ensure_checkout_tables($pdo);
    $form = shop_checkout_validate($formInput);
    $cart = shop_cart_details($pdo);
    if (count($cart['items']) === 0) {
        throw new RuntimeException('Your cart is empty.');
    }

    $shippingMethods = shop_shipping_methods();
    $shipping = $shippingMethods[$form['shipping_method']];
    $shippingFee = round((float)$shipping['fee'], 2);
    $subtotal = round((float)$cart['subtotal'], 2);
    $discountAmount = 0.0;
    $taxAmount = 0.0;
    $total = round($subtotal - $discountAmount + $taxAmount + $shippingFee, 2);

    $paymentMethod = $form['payment_method'];
    $paymentStatus = $paymentMethod === 'prepaid' ? 'paid' : 'unpaid';
    $shippingStatus = $shippingFee > 0 ? 'preparing' : 'not_shipped';
    $shipmentStatus = (string)($shipping['shipment_status'] ?? 'preparing');
    $shipmentStatus = in_array($shipmentStatus, ['preparing', 'shipped', 'in_transit', 'delivered', 'returned', 'cancelled'], true)
        ? $shipmentStatus
        : 'preparing';
    $orderStatus = $paymentMethod === 'prepaid' ? 'confirmed' : 'pending';

    $userId = shop_resolve_checkout_user_id($pdo, $form);
    $orderNumber = shop_generate_order_number($pdo);
    $placedAt = gmdate('Y-m-d H:i:s');

    $shippingPayload = [
        'full_name' => $form['full_name'],
        'phone' => $form['phone'],
        'address_line1' => $form['address_line1'],
        'address_line2' => $form['address_line2'],
        'city' => $form['city'],
        'township' => $form['township'],
        'state' => $form['state'],
        'country' => $form['country'],
        'postal_code' => $form['postal_code'],
    ];

    $pdo->beginTransaction();
    try {
        $orderStatement = $pdo->prepare(
            'INSERT INTO orders (
                order_number, user_id, subtotal, discount_amount, tax_amount, shipping_fee, total_amount,
                order_status, payment_status, shipping_status, payment_method, customer_note, placed_at, created_at, updated_at
            ) VALUES (
                :order_number, :user_id, :subtotal, :discount_amount, :tax_amount, :shipping_fee, :total_amount,
                :order_status, :payment_status, :shipping_status, :payment_method, :customer_note, :placed_at, NOW(), NOW()
            )'
        );
        $orderStatement->execute([
            ':order_number' => $orderNumber,
            ':user_id' => $userId,
            ':subtotal' => $subtotal,
            ':discount_amount' => $discountAmount,
            ':tax_amount' => $taxAmount,
            ':shipping_fee' => $shippingFee,
            ':total_amount' => $total,
            ':order_status' => $orderStatus,
            ':payment_status' => $paymentStatus,
            ':shipping_status' => $shippingStatus,
            ':payment_method' => $paymentMethod,
            ':customer_note' => shop_null_if_empty($form['customer_note']),
            ':placed_at' => $placedAt,
        ]);

        $orderId = (int)$pdo->lastInsertId();
        if ($orderId <= 0) {
            throw new RuntimeException('Failed to create order.');
        }

        $itemStatement = $pdo->prepare(
            'INSERT INTO order_items (
                order_id, product_id, product_variant_id, product_name, variant_name, sku, quantity, unit_price, total_price, created_at
            ) VALUES (
                :order_id, :product_id, :product_variant_id, :product_name, :variant_name, :sku, :quantity, :unit_price, :total_price, NOW()
            )'
        );

        $stockStatement = $pdo->prepare(
            'UPDATE product_variants
             SET stock_quantity = CASE WHEN stock_quantity >= :quantity_check THEN stock_quantity - :quantity_subtract ELSE 0 END,
                 updated_at = NOW()
             WHERE id = :variant_id'
        );

        foreach ($cart['items'] as $line) {
            $itemStatement->execute([
                ':order_id' => $orderId,
                ':product_id' => (int)$line['product_id'],
                ':product_variant_id' => (int)$line['variant_id'] > 0 ? (int)$line['variant_id'] : null,
                ':product_name' => (string)$line['product_name'],
                ':variant_name' => shop_null_if_empty((string)$line['variant_name']),
                ':sku' => shop_null_if_empty((string)$line['sku']),
                ':quantity' => (int)$line['quantity'],
                ':unit_price' => (float)$line['unit_price'],
                ':total_price' => (float)$line['line_total'],
            ]);

            $variantId = (int)$line['variant_id'];
            if ($variantId > 0 && shop_table_exists($pdo, 'product_variants')) {
                $stockStatement->execute([
                    ':quantity_check' => (int)$line['quantity'],
                    ':quantity_subtract' => (int)$line['quantity'],
                    ':variant_id' => $variantId,
                ]);
            }
        }

        if (shop_table_exists($pdo, 'shipments')) {
            $shippedAt = in_array($shipmentStatus, ['shipped', 'in_transit', 'delivered'], true) ? $placedAt : null;
            $deliveredAt = $shipmentStatus === 'delivered' ? $placedAt : null;
            $shipmentStatement = $pdo->prepare(
                'INSERT INTO shipments (
                    order_id, courier_name, tracking_number, shipping_address, shipping_fee, status, shipped_at, delivered_at, created_at, updated_at
                ) VALUES (
                    :order_id, :courier_name, :tracking_number, :shipping_address, :shipping_fee, :status, :shipped_at, :delivered_at, NOW(), NOW()
                )'
            );
            $shipmentStatement->execute([
                ':order_id' => $orderId,
                ':courier_name' => null,
                ':tracking_number' => null,
                ':shipping_address' => shop_encode_shipping_payload($shippingPayload),
                ':shipping_fee' => $shippingFee,
                ':status' => $shipmentStatus,
                ':shipped_at' => $shippedAt,
                ':delivered_at' => $deliveredAt,
            ]);
        }

        $pdo->commit();
        shop_cart_clear();

        return [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'total_amount' => $total,
            'payment_method' => $paymentMethod,
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}
