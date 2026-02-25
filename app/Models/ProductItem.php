<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductItem extends Model
{
    protected $fillable = ['product_id', 'sku', 'base_price', 'quantity_in_stock', 'status'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductItemVariant::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }
}
