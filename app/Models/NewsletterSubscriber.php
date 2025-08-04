<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'is_verified',
        'verification_token',
        'verified_at',
        'is_active',
        'preferences',
        'source',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
        'preferences' => 'array',
    ];

    /**
     * Generate a verification token.
     */
    public static function generateVerificationToken(): string
    {
        return Str::random(64);
    }

    /**
     * Scope to get only verified subscribers.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to get only active subscribers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Mark subscriber as verified.
     */
    public function markAsVerified(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verification_token' => null,
        ]);
    }

    /**
     * Unsubscribe the subscriber.
     */
    public function unsubscribe(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Resubscribe the subscriber.
     */
    public function resubscribe(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Get formatted subscription date.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('M j, Y');
    }

    /**
     * Check if subscriber needs verification.
     */
    public function needsVerification(): bool
    {
        return !$this->is_verified && $this->verification_token !== null;
    }
}
