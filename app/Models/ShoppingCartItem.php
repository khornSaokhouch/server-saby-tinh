<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShoppingCartItem extends Model
{
    protected $fillable = ['cart_id', 'product_item_variant_id', 'quantity'];

    public function cart()
    {
        return $this->belongsTo(ShoppingCart::class, 'cart_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductItemVariant::class, 'product_item_variant_id');
    }
}
