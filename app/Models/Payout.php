<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'store_id',
        'amount',
        'currency',
        'payment_status_id',
        'paid_at',
        'transaction_reference',
    ];

    /**
     * The invoice this payout belongs to.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * The store receiving this payout.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * The status of this payout.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(PaymentStatus::class, 'payment_status_id');
    }
}
