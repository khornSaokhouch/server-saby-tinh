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
    protected $appends = ['profile_image_url', 'accessible_store'];


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

     // Store where user is the primary owner
    public function ownedStore()
    {
        return $this->hasOne(Store::class, 'user_id');
    }

    // Alias for backward compatibility with existing code
    public function store()
    {
        return $this->ownedStore();
    }

    // Stores where user is a team member
    public function memberStores()
    {
        return $this->belongsToMany(Store::class, 'customer_list', 'user_id', 'store_id');
    }

    /**
     * Get the store the user belongs to (either as owner or member).
     * Named 'accessible_store' to avoid conflict with the store() relation method.
     * Frontend should read user.accessible_store
     */
    public function getAccessibleStoreAttribute()
    {
        // If ownedStore relation is loaded, use it
        if ($this->relationLoaded('ownedStore') && $this->ownedStore) {
            return $this->ownedStore;
        }

        // Try to fetch the owned store
        $owned = $this->ownedStore;
        if ($owned) return $owned;

        // If memberStores relation is loaded, use it
        if ($this->relationLoaded('memberStores') && $this->memberStores->isNotEmpty()) {
            return $this->memberStores->first();
        }

        // Fallback: query the customer_list directly for performance
        return $this->memberStores()->first();
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
        return $this->profile?->profile_image;
    }
}