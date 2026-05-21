-- Supabase/PostgreSQL index setup for e-commerce tables
-- Safe to run multiple times.

-- Optional: verify required tables exist in `public` schema.
select
    table_name
from information_schema.tables
where table_schema = 'public'
  and table_name in (
      'profiles',
      'addresses',
      'products',
      'product_variants',
      'carts',
      'cart_items',
      'orders',
      'order_items',
      'reviews'
  )
order by table_name;

create index if not exists idx_profiles_role on profiles(role);

create index if not exists idx_addresses_user_id on addresses(user_id);

create index if not exists idx_products_category_id on products(category_id);
create index if not exists idx_products_brand_id on products(brand_id);
create index if not exists idx_products_status on products(status);
create index if not exists idx_products_slug on products(slug);

create index if not exists idx_product_variants_product_id on product_variants(product_id);
create index if not exists idx_product_variants_sku on product_variants(sku);
create index if not exists idx_product_variants_active on product_variants(is_active);

create index if not exists idx_carts_user_id on carts(user_id);
create index if not exists idx_cart_items_cart_id on cart_items(cart_id);
create index if not exists idx_cart_items_variant_id on cart_items(product_variant_id);

create index if not exists idx_orders_user_id on orders(user_id);
create index if not exists idx_orders_order_number on orders(order_number);
create index if not exists idx_orders_status on orders(order_status);
create index if not exists idx_orders_created_at on orders(created_at);

create index if not exists idx_order_items_order_id on order_items(order_id);
create index if not exists idx_order_items_variant_id on order_items(product_variant_id);

create index if not exists idx_reviews_product_id on reviews(product_id);
create index if not exists idx_reviews_user_id on reviews(user_id);

-- Verify created indexes (run after CREATE INDEX statements)
select
    tablename,
    indexname,
    indexdef
from pg_indexes
where schemaname = 'public'
  and indexname in (
      'idx_profiles_role',
      'idx_addresses_user_id',
      'idx_products_category_id',
      'idx_products_brand_id',
      'idx_products_status',
      'idx_products_slug',
      'idx_product_variants_product_id',
      'idx_product_variants_sku',
      'idx_product_variants_active',
      'idx_carts_user_id',
      'idx_cart_items_cart_id',
      'idx_cart_items_variant_id',
      'idx_orders_user_id',
      'idx_orders_order_number',
      'idx_orders_status',
      'idx_orders_created_at',
      'idx_order_items_order_id',
      'idx_order_items_variant_id',
      'idx_reviews_product_id',
      'idx_reviews_user_id'
  )
order by tablename, indexname;
