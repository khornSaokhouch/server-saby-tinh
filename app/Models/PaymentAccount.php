<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAccount extends Model
{
    protected $fillable = [
        'user_id',
        'account_name',
        'account_id',
        'type_value',
        'account_city',
        'currency',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
