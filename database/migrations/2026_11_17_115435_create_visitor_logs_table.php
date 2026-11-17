<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('visited_at')->useCurrent()->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('user_type', 20)->default('guest')->index(); // guest, customer, seller, admin
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('session_id', 40)->nullable()->index();
            $table->string('method', 10)->nullable();
            $table->string('path', 500)->nullable();
            $table->string('full_url', 1000)->nullable();
            $table->string('route_name', 150)->nullable();
            $table->string('referrer', 1000)->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->string('device_type', 20)->nullable(); // desktop, mobile, tablet, bot, unknown
            $table->string('browser', 50)->nullable();
            $table->string('platform', 50)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->unsignedSmallInteger('status_code')->nullable()->index();
            $table->boolean('is_bot')->default(false)->index();
            $table->boolean('is_suspicious')->default(false)->index();
            $table->timestamps();

            $table->index(['visited_at', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_logs');
    }
};
