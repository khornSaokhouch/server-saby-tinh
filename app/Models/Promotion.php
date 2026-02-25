<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'name', 'description', 'discount_percentage', 'start_date', 'end_date'
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'promotion_category');
    }
}
