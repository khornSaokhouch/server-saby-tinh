<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'payment_method_id',
        'payment_status_id',
        'transaction_id',
        'amount',
        'currency',
        'paid_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(ShopOrder::class, 'order_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentAccount::class, 'payment_method_id');
    }

    public function paymentStatus()
    {
        return $this->belongsTo(PaymentStatus::class);
    }
}
