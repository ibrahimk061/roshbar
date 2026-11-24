<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeldCart extends Model
{
    protected $fillable = ['label', 'order_type', 'lines', 'item_count', 'total', 'user_id'];

    protected function casts(): array
    {
        return ['lines' => 'array', 'item_count' => 'integer', 'total' => 'decimal:2'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
