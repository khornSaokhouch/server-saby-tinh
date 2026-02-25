<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = ['store_id', 'product_item_id'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function productItem()
    {
        return $this->belongsTo(ProductItem::class);
    }
}
