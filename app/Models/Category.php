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
        'description',
        'image',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('home_collections_and_bestsellers');
            Cache::forget('home_page_data');
            Cache::forget('shop_sidebar_facets');
        });

        static::deleted(function () {
            Cache::forget('home_collections_and_bestsellers');
            Cache::forget('home_page_data');
            Cache::forget('shop_sidebar_facets');
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Public URL for the category tile image, or null when none was uploaded.
     * Homepage falls back to the bundled collection art in that case.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return file_exists(public_path('images/categories/'.$this->image))
            ? asset('images/categories/'.$this->image)
            : null;
    }
}
