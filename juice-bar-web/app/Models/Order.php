<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'uuid', 'order_number', 'business_date', 'sequence', 'order_type', 'status',
        'subtotal', 'discount_total', 'tax_total', 'grand_total', 'note', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'sequence' => 'integer',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function localCreatedAt(): \Carbon\CarbonInterface
    {
        return $this->created_at->clone()->setTimezone(config('pos.timezone'));
    }
}
