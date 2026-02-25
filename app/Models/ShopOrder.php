<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_date',
        'payment_method_id',
        'shipping_address_id',
        'shipping_method_id',
        'order_total',
        'order_status_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentAccount::class, 'payment_method_id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function orderStatus()
    {
        return $this->belongsTo(OrderStatus::class);
    }

    public function orderLines()
    {
        return $this->hasMany(OrderLine::class, 'order_id');
    }

    public function orderHistory()
    {
        return $this->hasMany(OrderHistory::class, 'order_id');
    }
}
