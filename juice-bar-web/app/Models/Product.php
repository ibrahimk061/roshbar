<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'sku', 'description', 'image_path',
        'is_active', 'is_available', 'sort_order',
        // website
        'slug', 'name_bn', 'description_bn', 'ingredients', 'ingredients_bn', 'color', 'garnish',
        'calories', 'is_seasonal', 'season_months', 'is_featured', 'show_on_website',
    ];

    public const GARNISHES = ['orange', 'lemon', 'kiwi', 'strawberry', 'watermelon', 'mango', 'berry', 'mint'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean', 'is_available' => 'boolean', 'sort_order' => 'integer',
            'calories' => 'integer', 'is_seasonal' => 'boolean', 'is_featured' => 'boolean', 'show_on_website' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(Addon::class, 'product_addons')->orderBy('sort_order')->orderBy('name');
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('sort_order')->orderBy('name');
    }

    public function scopeSellable(Builder $q): Builder
    {
        return $q->where('is_active', true)->where('is_available', true);
    }

    public function scopeOnWebsite(Builder $q): Builder
    {
        return $q->where('products.is_active', true)->where('products.show_on_website', true)->whereNotNull('products.slug');
    }

    /** @return int[] months 1-12 */
    public function seasonMonths(): array
    {
        return array_values(array_filter(array_map('intval', explode(',', (string) $this->season_months))));
    }

    /** Name used on the POS, orders, reports and receipts (config pos.item_language). */
    public function posName(): string
    {
        return config('pos.item_language') === 'bn' && filled($this->name_bn) ? $this->name_bn : $this->name;
    }

    /**
     * POS tile icon when an item has no photo: the first keyword found in the
     * item's English name, then its category slug, wins.
     */
    public const ICONS = [
        'lassi' => '🥛',
        'mango' => '🥭', 'watermelon' => '🍉', 'daab' => '🥥', 'coconut' => '🥥',
        'lychee' => '🌹', 'rose' => '🌹', 'bel' => '🍈', 'banana' => '🍌',
        'beet' => '🥕', 'carrot' => '🥕', 'green' => '🥬', 'detox' => '🥬',
        'jambura' => '🍊', 'malta' => '🍊', 'orange' => '🍊', 'amra' => '🫒',
        'lemon' => '🍋', 'pineapple' => '🍍', 'strawberry' => '🍓', 'grape' => '🍇',
        'apple' => '🍎', 'guava' => '🍐', 'papaya' => '🍈', 'pomegranate' => '🍎',
        'shake' => '🥤', 'smoothie' => '🥤', 'smoothies' => '🥤',
    ];

    /** Emoji shown on the POS tile when there is no photo. */
    public function icon(): string
    {
        $haystack = strtolower($this->name.' '.($this->category?->slug ?? ''));
        foreach (self::ICONS as $word => $icon) {
            if (preg_match('/\\b'.preg_quote($word, '/').'\\b/', $haystack)) {
                return $icon;
            }
        }

        return '🧃';
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
