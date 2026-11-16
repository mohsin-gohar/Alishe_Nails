<?php

namespace App\Models;

use App\Support\ImageUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Category extends Model
{
    use HasFactory;

    /**
     * Production may not have run the merchandising migration yet, so the
     * `is_active` / `sort_order` columns can be absent. Cache the result so we
     * don't hit information_schema on every query.
     *
     * @var array<string, bool>
     */
    protected static array $columnCache = [];

    public static function hasColumn(string $column): bool
    {
        if (! array_key_exists($column, static::$columnCache)) {
            static::$columnCache[$column] = Schema::hasColumn('categories', $column);
        }

        return static::$columnCache[$column];
    }

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
     * Only active categories, when the column exists. Production may not have
     * run the merchandising migration yet, so guard the filter.
     */
    public function scopeActive($query)
    {
        if (static::hasColumn('is_active')) {
            $query->where('is_active', true);
        }

        return $query;
    }

    /**
     * Stable ordering: sort_order when available, always fall back to name.
     */
    public function scopeOrdered($query)
    {
        if (static::hasColumn('sort_order')) {
            $query->orderBy('sort_order');
        }

        return $query->orderBy('name');
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
            ? ImageUrl::relativeUrl('images/categories/'.$this->image)
            : null;
    }
}
