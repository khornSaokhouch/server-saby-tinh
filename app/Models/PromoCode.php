<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'usage_count',
        'per_user_limit',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'status' => 'boolean',
    ];

    public function usages()
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    public function orders()
    {
        return $this->hasMany(ShopOrder::class);
    }

    public function isValidFor($subtotal)
    {
        if (!$this->status) return false;
        
        $now = now();
        if ($this->start_date && $now->lt($this->start_date)) return false;
        if ($this->end_date && $now->gt($this->end_date)) return false;
        
        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) return false;
        
        if ($this->min_order_amount && $subtotal < $this->min_order_amount) return false;
        
        return true;
    }
}
