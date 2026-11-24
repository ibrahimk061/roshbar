<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Addon extends Model
{
    protected $fillable = ['name', 'price', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_addons');
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('sort_order')->orderBy('name');
    }
}
