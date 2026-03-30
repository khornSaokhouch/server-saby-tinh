<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model {
    protected $fillable = ['name', 'brand_image', 'status', 'category_id'];
    protected $casts = ['status' => 'integer'];
}