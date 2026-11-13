<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Merchandising: which products the admin hand-picks for the
            // homepage "Curated For You" rail, and in what order.
            $table->boolean('is_homepage')->default(false);
            $table->unsignedInteger('homepage_order')->nullable();

            // Per-product SEO overrides. When empty the storefront falls back
            // to the product name + short description.
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 255)->nullable();

            // Comma-separated internal search keywords ("ombre, coffin, bridal").
            $table->string('tags', 255)->nullable();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'is_homepage',
                'homepage_order',
                'meta_title',
                'meta_description',
                'tags',
            ]);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['description', 'image', 'sort_order', 'is_active']);
        });
    }
};
