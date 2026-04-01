<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profile_image',
        'bio',
    ];

    /**
     * Relationship: profile belongs to user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //  // Optional: accessor to always return full URL
    // public function getImageProfileAttribute($value)
    // {
    //     return $value ? $value : null; // $value is already full ImageKit URL
    // }
}
