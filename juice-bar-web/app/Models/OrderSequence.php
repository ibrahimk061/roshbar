<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderSequence extends Model
{
    protected $fillable = ['business_date', 'last_number'];

    protected function casts(): array
    {
        return ['business_date' => 'date', 'last_number' => 'integer'];
    }
}
