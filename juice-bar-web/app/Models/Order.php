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
        'discount_type', 'discount_value', 'discount_reason', 'tax_rate', 'tax_inclusive',
        'paid_total', 'change_total', 'payment_status',
        'completed_at', 'cancelled_at', 'cancelled_by', 'cancel_reason',
    ];

    /** Kitchen / counter flow. Cancelled is reachable from any open status. */
    public const STATUSES = ['new' => 'New', 'preparing' => 'Preparing', 'ready' => 'Ready', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];

    public const NEXT_STATUS = ['new' => 'preparing', 'preparing' => 'ready', 'ready' => 'completed'];

    public function isOpen(): bool
    {
        return in_array($this->status, ['new', 'preparing', 'ready'], true);
    }

    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'sequence' => 'integer',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_inclusive' => 'boolean',
            'paid_total' => 'decimal:2',
            'change_total' => 'decimal:2',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class)->orderBy('id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
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
