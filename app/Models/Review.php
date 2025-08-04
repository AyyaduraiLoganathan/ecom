<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'rating',
        'title',
        'comment',
        'is_verified_purchase',
        'is_approved',
        'images',
        'helpful_count',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'images' => 'array',
        'helpful_count' => 'integer',
    ];

    /**
     * Get the user that owns the review.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product that belongs to the review.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the order that belongs to the review.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope to get only approved reviews.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope to get only verified purchase reviews.
     */
    public function scopeVerifiedPurchase($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Scope to get reviews by rating.
     */
    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Get star rating as array for display.
     */
    public function getStarsAttribute(): array
    {
        $stars = [];
        for ($i = 1; $i <= 5; $i++) {
            $stars[] = $i <= $this->rating;
        }
        return $stars;
    }

    /**
     * Get formatted date.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('M j, Y');
    }

    /**
     * Get reviewer name (with privacy protection).
     */
    public function getReviewerNameAttribute(): string
    {
        if (!$this->user) {
            return 'Anonymous';
        }

        $name = $this->user->name;
        $parts = explode(' ', $name);
        
        if (count($parts) > 1) {
            return $parts[0] . ' ' . substr($parts[1], 0, 1) . '.';
        }

        return substr($name, 0, 1) . str_repeat('*', strlen($name) - 2) . substr($name, -1);
    }

    /**
     * Check if review is helpful.
     */
    public function isHelpful(): bool
    {
        return $this->helpful_count > 0;
    }

    /**
     * Get rating text.
     */
    public function getRatingTextAttribute(): string
    {
        return match ($this->rating) {
            5 => 'Excellent',
            4 => 'Very Good',
            3 => 'Good',
            2 => 'Fair',
            1 => 'Poor',
            default => 'Not Rated',
        };
    }

    /**
     * Get truncated comment for preview.
     */
    public function getTruncatedCommentAttribute(): string
    {
        if (!$this->comment) {
            return '';
        }

        return strlen($this->comment) > 150 
            ? substr($this->comment, 0, 150) . '...' 
            : $this->comment;
    }
}
