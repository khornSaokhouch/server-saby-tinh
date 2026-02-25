<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSocial extends Model
{
    use HasFactory;

    // Table name (optional if it follows Laravel convention)
    protected $table = 'user_social';

    // Mass assignable attributes
    protected $fillable = [
        'user_id',
        'provider',
        'social_id',
        'avatar',
    ];

    /**
     * Relationship: Each social account belongs to a user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
