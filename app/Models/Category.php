<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

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

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
