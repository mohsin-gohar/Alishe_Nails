<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('is_active');
            $table->index('category_id');
            $table->index(['is_active', 'is_best_seller']);
            $table->index(['is_active', 'price']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('created_at');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['product_id', 'is_approved']);
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'is_approved']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['products_is_active_index']);
            $table->dropIndex(['products_category_id_index']);
            $table->dropIndex(['products_is_active_is_best_seller_index']);
            $table->dropIndex(['products_is_active_price_index']);
        });
    }
};
