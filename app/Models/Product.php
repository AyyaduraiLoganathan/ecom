<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'short_description',
        'sku',
        'price',
        'sale_price',
        'stock_quantity',
        'manage_stock',
        'in_stock',
        'status',
        'featured_image',
        'gallery_images',
        'weight',
        'dimensions',
        'is_featured',
        'is_digital',
        'meta_title',
        'meta_description',
        'attributes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'manage_stock' => 'boolean',
        'in_stock' => 'boolean',
        'is_featured' => 'boolean',
        'is_digital' => 'boolean',
        'gallery_images' => 'array',
        'attributes' => 'array',
        'views_count' => 'integer',
        'reviews_count' => 'integer',
    ];

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the cart items for the product.
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get the wishlist items for the product.
     */
    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    /**
     * Get the order items for the product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the reviews for the product.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    /**
     * Get all reviews (including unapproved).
     */
    public function allReviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Scope to get only active products.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get only featured products.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to get only in-stock products.
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('in_stock', true);
    }

    /**
     * Scope to filter by price range.
     */
    public function scopePriceRange(Builder $query, $min = null, $max = null): Builder
    {
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }
        
        if ($max !== null) {
            $query->where('price', '<=', $max);
        }

        return $query;
    }

    /**
     * Scope to search products.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('short_description', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%");
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the current price (sale price if available, otherwise regular price).
     */
    public function getCurrentPriceAttribute(): float
    {
        return $this->sale_price ?? $this->price;
    }

    /**
     * Check if product is on sale.
     */
    public function getIsOnSaleAttribute(): bool
    {
        return $this->sale_price !== null && $this->sale_price < $this->price;
    }

    /**
     * Get discount percentage.
     */
    public function getDiscountPercentageAttribute(): int
    {
        if (!$this->is_on_sale) {
            return 0;
        }

        return round((($this->price - $this->sale_price) / $this->price) * 100);
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->current_price, 2);
    }

    /**
     * Get formatted original price.
     */
    public function getFormattedOriginalPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    /**
     * Check if product is available for purchase.
     */
    public function getIsAvailableAttribute(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->manage_stock && $this->stock_quantity <= 0) {
            return false;
        }

        return $this->in_stock;
    }

    /**
     * Get stock status text.
     */
    public function getStockStatusAttribute(): string
    {
        if (!$this->is_available) {
            return 'Out of Stock';
        }

        if ($this->manage_stock) {
            if ($this->stock_quantity <= 5) {
                return "Only {$this->stock_quantity} left";
            }
            return 'In Stock';
        }

        return 'In Stock';
    }

    /**
     * Get primary image URL.
     */
    public function getImageUrlAttribute(): string
    {
        return $this->featured_image ?? '/images/placeholder-product.jpg';
    }

    /**
     * Get all gallery images including featured image.
     */
    public function getAllImagesAttribute(): array
    {
        $images = [];
        
        if ($this->featured_image) {
            $images[] = $this->featured_image;
        }

        if ($this->gallery_images) {
            $images = array_merge($images, $this->gallery_images);
        }

        return array_unique($images);
    }

    /**
     * Increment views count.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Update average rating and reviews count.
     */
    public function updateRatingStats(): void
    {
        $reviews = $this->reviews();
        $this->update([
            'average_rating' => $reviews->avg('rating') ?? 0,
            'reviews_count' => $reviews->count(),
        ]);
    }
}
