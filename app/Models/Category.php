<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'category_image', 'status'];

    protected $casts = [
        'status' => 'integer',
    ];

    public function types()
    {
        return $this->hasMany(Type::class);
    }

    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'promotion_category');
    }

    public function colors()
    {
        return $this->belongsToMany(Color::class, 'category_color');
    }

    public function sizes()
    {
        return $this->belongsToMany(Size::class, 'category_size');
    }
}
