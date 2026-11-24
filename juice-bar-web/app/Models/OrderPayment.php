<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    protected $fillable = ['order_id', 'payment_method_id', 'method_name', 'amount', 'tendered', 'change', 'reference', 'status', 'received_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'tendered' => 'decimal:2', 'change' => 'decimal:2'];
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
