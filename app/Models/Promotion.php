<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'user_id', 'name', 'description', 'discount_percentage', 'start_date', 'end_date', 'status'
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'promotion_category')->withPivot(['user_id', 'status']);
    }
}
