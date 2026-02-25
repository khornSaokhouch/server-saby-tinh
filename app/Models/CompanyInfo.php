<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyInfo extends Model
{
    protected $table = 'companies_info';

    protected $fillable = [
        'user_id',
        'company_name',
        'company_image',
        'description',
        'website_url',
        'open_time',
        'close_time',
        'facebook_url',
        'instagram_url',
        'twitter_url',
        'linkedin_url',
        'address_id',
    ];

    /**
     * Get the user that owns the company info.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the address associated with the company info.
     */
    public function address()
    {
        return $this->belongsTo(Address::class);
    }
}
