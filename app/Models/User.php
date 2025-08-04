<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'date_of_birth',
        'gender',
        'marketing_emails',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'date_of_birth' => 'date',
        'is_admin' => 'boolean',
        'marketing_emails' => 'boolean',
    ];

    /**
     * Get the cart items for the user.
     */
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get the wishlist items for the user.
     */
    public function wishlistItems()
    {
        return $this->hasMany(WishlistItem::class);
    }

    /**
     * Get the orders for the user.
     */
    public function orders()
    {
        return $this->hasMany(Order::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the reviews for the user.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Get full address as string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get cart total count.
     */
    public function getCartCountAttribute(): int
    {
        return $this->cartItems()->sum('quantity');
    }

    /**
     * Get wishlist total count.
     */
    public function getWishlistCountAttribute(): int
    {
        return $this->wishlistItems()->count();
    }
}
