<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorLog extends Model
{
    protected $fillable = [
        'visited_at',
        'ip_address',
        'user_type',
        'user_id',
        'session_id',
        'method',
        'path',
        'full_url',
        'route_name',
        'referrer',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'country',
        'region',
        'city',
        'status_code',
        'is_bot',
        'is_suspicious',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'is_bot' => 'boolean',
        'is_suspicious' => 'boolean',
    ];
}
