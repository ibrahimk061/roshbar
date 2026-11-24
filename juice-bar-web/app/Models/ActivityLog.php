<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Append-only staff activity record. */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    public const ACTIONS = [
        'login' => 'Signed in',
        'login.failed' => 'Wrong password',
        'logout' => 'Signed out',
        'password.change' => 'Changed own password',
        'order.discount' => 'Gave a discount',
        'order.cancel' => 'Cancelled an order',
        'order.payment' => 'Took a later payment',
        'expense.create' => 'Added an expense',
        'expense.update' => 'Edited an expense',
        'expense.delete' => 'Deleted an expense',
        'product.create' => 'Added a product',
        'product.update' => 'Edited a product',
        'settings.update' => 'Changed settings',
        'user.create' => 'Added a staff member',
        'user.update' => 'Edited a staff member',
    ];

    protected $fillable = ['user_id', 'action', 'subject_type', 'subject_id', 'description', 'ip'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
