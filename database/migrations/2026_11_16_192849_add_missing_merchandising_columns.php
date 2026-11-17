<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add missing columns to categories table
        if (! Schema::hasColumn('categories', 'is_active')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->boolean('is_active')->default(true);
            });
        }

        if (! Schema::hasColumn('categories', 'sort_order')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0);
            });
        }

        if (! Schema::hasColumn('categories', 'description')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->text('description')->nullable();
            });
        }

        if (! Schema::hasColumn('categories', 'image')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('image')->nullable();
            });
        }

        // Add missing columns to products table
        if (! Schema::hasColumn('products', 'is_homepage')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('is_homepage')->default(false);
            });
        }

        if (! Schema::hasColumn('products', 'homepage_order')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedInteger('homepage_order')->nullable();
            });
        }

        if (! Schema::hasColumn('products', 'meta_title')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('meta_title', 70)->nullable();
            });
        }

        if (! Schema::hasColumn('products', 'meta_description')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('meta_description', 255)->nullable();
            });
        }

        if (! Schema::hasColumn('products', 'tags')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('tags', 255)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // safely drop columns only if they exist (added by this migration)
        // using a try-catch approach since dropColumn throws if column doesn't exist

        // Products table - reverse in reverse order
        if (Schema::hasColumn('products', 'tags')) {
            try {
                Schema::table('products', function (Blueprint $table) {
                    $table->dropColumn('tags');
                });
            } catch (Exception $e) {
                // Column may have been modified or removed already
            }
        }

        if (Schema::hasColumn('products', 'meta_description')) {
            try {
                Schema::table('products', function (Blueprint $table) {
                    $table->dropColumn('meta_description');
                });
            } catch (Exception $e) {
                // Column may have been modified or removed already
            }
        }

        if (Schema::hasColumn('products', 'meta_title')) {
            try {
                Schema::table('products', function (Blueprint $table) {
                    $table->dropColumn('meta_title');
                });
            } catch (Exception $e) {
                // Column may have been modified or removed already
            }
        }

        if (Schema::hasColumn('products', 'homepage_order')) {
            try {
                Schema::table('products', function (Blueprint $table) {
                    $table->dropColumn('homepage_order');
                });
            } catch (Exception $e) {
                // Column may have been modified or removed already
            }
        }

        if (Schema::hasColumn('products', 'is_homepage')) {
            try {
                Schema::table('products', function (Blueprint $table) {
                    $table->dropColumn('is_homepage');
                });
            } catch (Exception $e) {
                // Column may have been modified or removed already
            }
        }

        // Categories table - reverse in reverse order
        if (Schema::hasColumn('categories', 'image')) {
            try {
                Schema::table('categories', function (Blueprint $table) {
                    $table->dropColumn('image');
                });
            } catch (Exception $e) {
                // Column may have been modified or removed already
            }
        }

        if (Schema::hasColumn('categories', 'description')) {
            try {
                Schema::table('categories', function (Blueprint $table) {
                    $table->dropColumn('description');
                });
            } catch (Exception $e) {
                // Column may have been modified or removed already
            }
        }

        if (Schema::hasColumn('categories', 'sort_order')) {
            try {
                Schema::table('categories', function (Blueprint $table) {
                    $table->dropColumn('sort_order');
                });
            } catch (Exception $e) {
                // Column may have been modified or removed already
            }
        }

        if (Schema::hasColumn('categories', 'is_active')) {
            try {
                Schema::table('categories', function (Blueprint $table) {
                    $table->dropColumn('is_active');
                });
            } catch (Exception $e) {
                // Column may have been modified or removed already
            }
        }
    }
};
