<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'name', 'price', 'is_default', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        // decimal:2 keeps money as a string, never a float.
        return ['price' => 'decimal:2', 'is_default' => 'boolean', 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
