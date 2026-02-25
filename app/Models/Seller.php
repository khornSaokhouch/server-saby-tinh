<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Seller extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'company_name',
        'email',
        'country_region',
        'street_address',
        'phone_number',
        'document_path',
        'status',
    ];

    // 🔹 Add computed field to JSON output
    protected $appends = ['document_url'];

    public function getDocumentUrlAttribute()
    {
        return $this->document_path;
    }
}
