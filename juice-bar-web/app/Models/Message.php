<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Contact-form message from the public website. */
class Message extends Model
{
    public const TOPICS = ['general', 'catering', 'feedback'];

    protected $fillable = ['name', 'phone', 'topic', 'message', 'is_read', 'ip'];

    protected function casts(): array
    {
        return ['is_read' => 'boolean'];
    }
}
