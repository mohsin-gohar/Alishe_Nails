<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enterprise performance indexes.
 *
 * Every index here backs a query the storefront actually runs:
 *
 *  products
 *    (is_active, is_homepage, homepage_order) -> the Homepage Studio lineup
 *    (is_active, is_featured)                 -> "featured" merchandising
 *    (is_active, created_at)                  -> the default "newest first" sort
 *    (is_active, shape|length|finish)         -> the shop facet filters
 *    (is_active, stock)                       -> stock / low-stock filters
 *    (is_active, seller_id)                   -> marketplace listings
 *
 *  categories
 *    (is_active, sort_order)                  -> category rails and the
 *                                                 admin category order
 *
 *  orders
 *    (status, created_at)                     -> the admin revenue report
 *
 *  order_items
 *    (product_id)                             -> "top products by units sold"
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex('products', ['is_active', 'is_homepage', 'homepage_order'], 'products_homepage_lineup_index');
        $this->addIndex('products', ['is_active', 'is_featured'], 'products_active_featured_index');
        $this->addIndex('products', ['is_active', 'created_at'], 'products_active_created_index');
        $this->addIndex('products', ['is_active', 'shape'], 'products_active_shape_index');
        $this->addIndex('products', ['is_active', 'length'], 'products_active_length_index');
        $this->addIndex('products', ['is_active', 'finish'], 'products_active_finish_index');
        $this->addIndex('products', ['is_active', 'stock'], 'products_active_stock_index');
        $this->addIndex('products', ['is_active', 'seller_id'], 'products_active_seller_index');

        $this->addIndex('categories', ['is_active', 'sort_order'], 'categories_active_sort_index');

        $this->addIndex('orders', ['status', 'created_at'], 'orders_status_created_index');

        $this->addIndex('order_items', ['product_id'], 'order_items_product_id_index');
    }

    public function down(): void
    {
        $this->dropIndex('products', 'products_homepage_lineup_index');
        $this->dropIndex('products', 'products_active_featured_index');
        $this->dropIndex('products', 'products_active_created_index');
        $this->dropIndex('products', 'products_active_shape_index');
        $this->dropIndex('products', 'products_active_length_index');
        $this->dropIndex('products', 'products_active_finish_index');
        $this->dropIndex('products', 'products_active_stock_index');
        $this->dropIndex('products', 'products_active_seller_index');

        $this->dropIndex('categories', 'categories_active_sort_index');

        $this->dropIndex('orders', 'orders_status_created_index');

        $this->dropIndex('order_items', 'order_items_product_id_index');
    }

    /**
     * Adds a composite index when the table exists and the index is not
     * already present (so the migration is safe to re-run on a shared host).
     */
    private function addIndex(string $table, array $columns, string $name): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach (Schema::getIndexes($table) as $existing) {
            if ($existing['name'] === $name) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name) {
            $blueprint->index($columns, $name);
        });
    }

    private function dropIndex(string $table, string $name): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach (Schema::getIndexes($table) as $existing) {
            if ($existing['name'] === $name) {
                Schema::table($table, function (Blueprint $blueprint) use ($name) {
                    $blueprint->dropIndex($name);
                });

                return;
            }
        }
    }
};
