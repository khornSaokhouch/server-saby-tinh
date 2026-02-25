<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_line_id',
        'review_text',
        'rating',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderLine()
    {
        return $this->belongsTo(OrderLine::class);
    }
}
