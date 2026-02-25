<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductItemVariant extends Model
{
    protected $fillable = [
        'product_item_id', 'color_id', 'size_id', 'price_modifier', 'quantity_in_stock', 'status'
    ];

    public function productItem()
    {
        return $this->belongsTo(ProductItem::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }

    public function size()
    {
        return $this->belongsTo(Size::class);
    }
}
