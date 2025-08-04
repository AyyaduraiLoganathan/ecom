<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WishlistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'product_id',
    ];

    /**
     * Get the user that owns the wishlist item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product that belongs to the wishlist item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to get wishlist items for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get wishlist items for a specific session.
     */
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Check if product is still available.
     */
    public function isProductAvailable(): bool
    {
        return $this->product && $this->product->is_available;
    }

    /**
     * Get the date when item was added to wishlist.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('M j, Y');
    }
}
