<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

class Seller extends Authenticatable
{
    use Notifiable;

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('home_collections_and_bestsellers');
            Cache::forget('shop_sidebar_facets');
        });

        static::deleted(function () {
            Cache::forget('home_collections_and_bestsellers');
            Cache::forget('shop_sidebar_facets');
        });
    }

    protected $fillable = [
        'name', 'email', 'instagram_handle', 'phone', 'product_details', 'password', 'status', 'approved_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['approved_at' => 'datetime'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(SellerSubscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(SellerSubscription::class)->where('status', 'active')->where('expires_at', '>', now())->latestOfMany();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function getPayoutDueAttribute(): float
    {
        return (float) $this->orderItems()
            ->where('payout_status', 'pending')
            ->whereHas('order', fn ($query) => $query->where('status', '!=', 'cancelled'))
            ->sum('seller_earning');
    }
}
