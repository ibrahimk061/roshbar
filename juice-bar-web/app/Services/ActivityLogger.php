<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityLogger
{
    public static function log(string $action, string $description, ?Model $subject = null, ?User $user = null): void
    {
        ActivityLog::query()->create([
            'user_id' => ($user ?? auth()->user())?->id,
            'action' => $action,
            'subject_type' => $subject ? Str::snake(class_basename($subject)) : null,
            'subject_id' => $subject?->getKey(),
            'description' => Str::limit($description, 495),
            'ip' => request()?->ip(),
        ]);
    }
}
