<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeaderNotificationRead extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'notification_key',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }
}
