<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'city',
        'area',
        'delivery_fee',
        'free_shipping_threshold',
        'is_active',
    ];

    protected $casts = [
        'delivery_fee' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Alishe Nails delivers exclusively inside Karachi at one flat rate.
     *
     * The rate used to be looked up per area and could fall back to "free"
     * above a cart threshold, which made the checkout show "Coordinated
     * directly" instead of a price. Delivery is now always this amount, so the
     * cart, the checkout summary, the Place Order button and the stored order
     * can never disagree.
     */
    public const FLAT_FEE = 300.00;

    /**
     * The flat delivery fee, with the zone label for display.
     *
     * @return array{fee: float, standard_fee: float, threshold: null, is_free: false, zone_label: string}
     */
    public static function calculateFee(string $city, ?string $area, float $subtotal): array
    {
        $city = trim($city);
        $area = $area ? trim($area) : null;

        // Keep the label accurate even though the fee no longer depends on the
        // row: "Karachi (DHA)" reads better than a bare city name.
        $zoneLabel = $city === '' ? 'Karachi' : $city;

        if ($area !== null && $area !== '') {
            $rate = self::where('is_active', true)
                ->where('city', $city)
                ->where('area', $area)
                ->first();

            if ($rate) {
                $zoneLabel = $city.' ('.$area.')';
            }
        }

        return [
            'fee' => self::FLAT_FEE,
            'standard_fee' => self::FLAT_FEE,
            'threshold' => null,
            'is_free' => false,
            'zone_label' => $zoneLabel,
        ];
    }
}
