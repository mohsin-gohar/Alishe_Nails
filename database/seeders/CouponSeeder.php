<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME10',
                'type' => 'percent',
                'value' => 10,
                'usage_limit' => 500,
                'used_count' => 0,
                'expires_at' => now()->addYear(),
                'is_active' => true,
            ],
            [
                'code' => 'ALISHE500',
                'type' => 'fixed',
                'value' => 500,
                'usage_limit' => 200,
                'used_count' => 0,
                'expires_at' => now()->addYear(),
                'is_active' => true,
            ],
            [
                'code' => 'GLAM20',
                'type' => 'percent',
                'value' => 20,
                'usage_limit' => 100,
                'used_count' => 0,
                'expires_at' => now()->addMonths(6),
                'is_active' => true,
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::updateOrCreate(
                ['code' => $coupon['code']],
                $coupon
            );
        }
    }
}
