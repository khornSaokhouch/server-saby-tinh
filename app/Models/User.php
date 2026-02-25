<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;


class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    const ROLE_USER  = 'user';
    const ROLE_ADMIN = 'admin';
    const ROLE_OWNER = 'owner';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'role',
    ];

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array
     */
    protected $appends = ['profile_image_url'];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // JWT methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // get profile
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

     // store 
    public function store()
    {
        return $this->hasOne(Store::class);
    }

    public function companyInfo()
    {
        return $this->hasOne(CompanyInfo::class);
    }

    public function paymentAccounts()
    {
        return $this->hasMany(PaymentAccount::class);
    }

    public function socialAccounts()
    {
        return $this->hasMany(UserSocial::class);
    }

    public function addresses()
    {
        return $this->belongsToMany(Address::class, 'user_address');
    }

    public function shopOrders()
    {
        return $this->hasMany(ShopOrder::class);
    }

    public function reviews()
    {
        return $this->hasMany(UserReview::class);
    }

    public function orderHistory()
    {
        return $this->hasMany(OrderHistory::class);
    }

    /**
     * Get the user's profile image URL.
     *
     * @return string|null
     */
    public function getProfileImageUrlAttribute()
    {
        return $this->profile?->image_profile;
    }
}