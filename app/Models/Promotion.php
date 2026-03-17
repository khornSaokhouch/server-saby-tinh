<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'user_id', 
        'name', 
        'description', 
        'priority',
        'event_type',
        'discount_type',
        'discount_value',
        'start_date', 
        'end_date', 
        'status'
    ];

    protected $casts = [
        'status' => 'integer',
        'priority' => 'integer',
        'discount_value' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'promotion_category');
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
