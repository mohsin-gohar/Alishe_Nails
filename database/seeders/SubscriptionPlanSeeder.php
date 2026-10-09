<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Monthly Creator',
                'duration_days' => 30,
                'price' => 2500,
                'description' => 'List unlimited custom nail designs in the Alishe Nails marketplace for 30 days with 0% platform commission.',
                'is_active' => true,
            ],
            [
                'name' => 'Quarterly Pro',
                'duration_days' => 90,
                'price' => 6500,
                'description' => 'List unlimited custom nail designs for 3 months with priority storefront placement and 0% commission.',
                'is_active' => true,
            ],
            [
                'name' => 'Annual Partner',
                'duration_days' => 365,
                'price' => 22000,
                'description' => 'Full 1-year verified storefront membership with featured collection tags and dedicated brand support.',
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['name' => $plan['name']],
                $plan
            );
        }
    }
}
