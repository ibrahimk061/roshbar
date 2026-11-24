<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemAddon extends Model
{
    protected $fillable = ['order_item_id', 'addon_id', 'addon_name', 'price'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }
}
