<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// app/Models/UserOtp.php
class UserOtp extends Model
{
    protected $fillable = [
        'user_id',
        'otp',
        'expires_at',
        'is_used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
    ];
}

