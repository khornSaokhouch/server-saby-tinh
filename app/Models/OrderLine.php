<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_item_variant_id',
        'quantity',
        'price',
    ];

    public function order()
    {
        return $this->belongsTo(ShopOrder::class, 'order_id');
    }

    public function productItemVariant()
    {
        return $this->belongsTo(ProductItemVariant::class);
    }

    public function review()
    {
        return $this->hasOne(UserReview::class, 'order_line_id');
    }
}
