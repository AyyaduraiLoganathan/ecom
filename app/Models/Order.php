<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'payment_status',
        'payment_method',
        'payment_id',
        'billing_address',
        'shipping_address',
        'shipping_method',
        'tracking_number',
        'shipped_at',
        'delivered_at',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order items for the order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Generate a unique order number.
     */
    public static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . date('Y') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Scope to get orders by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get paid orders.
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Get formatted total amount.
     */
    public function getFormattedTotalAttribute(): string
    {
        return '$' . number_format($this->total_amount, 2);
    }

    /**
     * Get formatted subtotal.
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return '$' . number_format($this->subtotal, 2);
    }

    /**
     * Get formatted tax amount.
     */
    public function getFormattedTaxAttribute(): string
    {
        return '$' . number_format($this->tax_amount, 2);
    }

    /**
     * Get formatted shipping amount.
     */
    public function getFormattedShippingAttribute(): string
    {
        return '$' . number_format($this->shipping_amount, 2);
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'processing' => 'blue',
            'shipped' => 'purple',
            'delivered' => 'green',
            'cancelled' => 'red',
            'refunded' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get payment status badge color.
     */
    public function getPaymentStatusColorAttribute(): string
    {
        return match ($this->payment_status) {
            'pending' => 'yellow',
            'paid' => 'green',
            'failed' => 'red',
            'refunded' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Check if order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    /**
     * Check if order can be refunded.
     */
    public function canBeRefunded(): bool
    {
        return $this->payment_status === 'paid' && 
               in_array($this->status, ['pending', 'processing', 'shipped', 'delivered']);
    }

    /**
     * Get order timeline.
     */
    public function getTimelineAttribute(): array
    {
        $timeline = [
            [
                'status' => 'Order Placed',
                'date' => $this->created_at,
                'completed' => true,
            ],
        ];

        if ($this->payment_status === 'paid') {
            $timeline[] = [
                'status' => 'Payment Confirmed',
                'date' => $this->updated_at,
                'completed' => true,
            ];
        }

        if (in_array($this->status, ['processing', 'shipped', 'delivered'])) {
            $timeline[] = [
                'status' => 'Processing',
                'date' => $this->updated_at,
                'completed' => true,
            ];
        }

        if (in_array($this->status, ['shipped', 'delivered'])) {
            $timeline[] = [
                'status' => 'Shipped',
                'date' => $this->shipped_at,
                'completed' => true,
            ];
        }

        if ($this->status === 'delivered') {
            $timeline[] = [
                'status' => 'Delivered',
                'date' => $this->delivered_at,
                'completed' => true,
            ];
        }

        return $timeline;
    }

    /**
     * Get total items count.
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Get billing address as formatted string.
     */
    public function getFormattedBillingAddressAttribute(): string
    {
        if (!$this->billing_address) {
            return '';
        }

        $address = $this->billing_address;
        return implode(', ', array_filter([
            $address['address'] ?? '',
            $address['city'] ?? '',
            $address['state'] ?? '',
            $address['postal_code'] ?? '',
            $address['country'] ?? '',
        ]));
    }

    /**
     * Get shipping address as formatted string.
     */
    public function getFormattedShippingAddressAttribute(): string
    {
        if (!$this->shipping_address) {
            return '';
        }

        $address = $this->shipping_address;
        return implode(', ', array_filter([
            $address['address'] ?? '',
            $address['city'] ?? '',
            $address['state'] ?? '',
            $address['postal_code'] ?? '',
            $address['country'] ?? '',
        ]));
    }
}
