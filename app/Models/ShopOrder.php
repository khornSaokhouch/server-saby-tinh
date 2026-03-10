<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\PaymentAccount;
use App\Models\Address;
use App\Models\ShippingMethod;
use App\Models\OrderStatus;
use App\Models\PaymentStatus;
use App\Models\OrderLine;
use App\Models\OrderHistory;
use App\Models\UserPayment;
use App\Models\PromoCode;
use App\Models\Invoice;

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
        'subtotal',
        'discount_amount',
        'shipping_fee',
        'promo_code_id',
        'order_status_id',
        'payment_status_id',
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

    public function paymentStatus()
    {
        return $this->belongsTo(PaymentStatus::class);
    }

    public function orderLines()
    {
        return $this->hasMany(OrderLine::class, 'order_id');
    }

    public function orderHistory()
    {
        return $this->hasMany(OrderHistory::class, 'order_id');
    }

    public function userPayments()
    {
        return $this->hasMany(UserPayment::class, 'order_id');
    }

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'order_id');
    }

    public function recordPromoUsage()
    {
        if ($this->promo_code_id && $this->payment_status_id == 2) {
            $exists = \App\Models\PromoCodeUsage::where('order_id', $this->id)->exists();
            if (!$exists) {
                \App\Models\PromoCodeUsage::create([
                    'promo_code_id' => $this->promo_code_id,
                    'user_id' => $this->user_id,
                    'order_id' => $this->id,
                    'discount_amount' => $this->discount_amount,
                    'used_at' => now(),
                ]);

                if ($this->promoCode) $this->promoCode->increment('usage_count');
            }
        }
    }

    public function updateInvoiceStatus()
    {
        $this->loadMissing('invoice');
        $invoice = $this->invoice;
        if ($invoice) {
            $invoice->update([
                'payment_status_id' => $this->payment_status_id,
                'generated_at' => ($this->payment_status_id == 2 && !$invoice->generated_at) ? now() : $invoice->generated_at
            ]);
        }
    }
}
