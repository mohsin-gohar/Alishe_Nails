<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use Illuminate\Database\Seeder;

class ShippingRateSeeder extends Seeder
{
    /**
     * Alishe Nails delivers exclusively across Karachi.
     * Shipping rates are categorized by Karachi delivery zones.
     */
    public function run(): void
    {
        $rates = [
            ['city' => 'Karachi', 'area' => 'Korangi / Landhi', 'delivery_fee' => 150, 'free_shipping_threshold' => 5000],
            ['city' => 'Karachi', 'area' => 'Shah Faisal Colony', 'delivery_fee' => 150, 'free_shipping_threshold' => 5000],
            ['city' => 'Karachi', 'area' => 'Malir / Model Colony', 'delivery_fee' => 180, 'free_shipping_threshold' => 5000],
            ['city' => 'Karachi', 'area' => 'Gulshan-e-Iqbal', 'delivery_fee' => 180, 'free_shipping_threshold' => 5000],
            ['city' => 'Karachi', 'area' => 'Gulistan-e-Johar', 'delivery_fee' => 180, 'free_shipping_threshold' => 5000],
            ['city' => 'Karachi', 'area' => 'PECHS / Tariq Road / Bahadurabad', 'delivery_fee' => 180, 'free_shipping_threshold' => 5000],
            ['city' => 'Karachi', 'area' => 'Saddar / Cantt / Garden', 'delivery_fee' => 180, 'free_shipping_threshold' => 5000],
            ['city' => 'Karachi', 'area' => 'Federal B Area (F.B Area)', 'delivery_fee' => 190, 'free_shipping_threshold' => 5000],
            ['city' => 'Karachi', 'area' => 'DHA (Defence)', 'delivery_fee' => 200, 'free_shipping_threshold' => 5000],
            ['city' => 'Karachi', 'area' => 'Clifton', 'delivery_fee' => 200, 'free_shipping_threshold' => 5000],
            ['city' => 'Karachi', 'area' => 'Nazimabad / North Nazimabad', 'delivery_fee' => 200, 'free_shipping_threshold' => 5000],
            ['city' => 'Karachi', 'area' => 'North Karachi / New Karachi', 'delivery_fee' => 200, 'free_shipping_threshold' => 5000],
            ['city' => 'Karachi', 'area' => 'Scheme 33 / Gulshan-e-Maymar', 'delivery_fee' => 220, 'free_shipping_threshold' => 5000],
            ['city' => 'Karachi', 'area' => 'Orangi / Site / Baldia', 'delivery_fee' => 220, 'free_shipping_threshold' => 5000],
            ['city' => 'Karachi', 'area' => 'Bahria Town Karachi', 'delivery_fee' => 250, 'free_shipping_threshold' => 5000],
            ['city' => 'Karachi', 'area' => 'Other Karachi Areas', 'delivery_fee' => 200, 'free_shipping_threshold' => 5000],
            ['city' => 'Karachi', 'area' => null, 'delivery_fee' => 200, 'free_shipping_threshold' => 5000],
        ];

        ShippingRate::where('city', '!=', 'Karachi')->delete();

        foreach ($rates as $rate) {
            ShippingRate::updateOrCreate(
                ['city' => $rate['city'], 'area' => $rate['area']],
                [
                    'delivery_fee' => $rate['delivery_fee'],
                    'free_shipping_threshold' => $rate['free_shipping_threshold'],
                    'is_active' => true,
                ]
            );
        }
    }
}
