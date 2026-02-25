<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'category_image', 'status'];

    protected $casts = [
        'status' => 'integer', // Ensures status always returns as a number
    ];

    public function types()
    {
        return $this->hasMany(Type::class);
    }

    public function promotions()
{
    return $this->belongsToMany(Promotion::class, 'promotion_category');
}

}