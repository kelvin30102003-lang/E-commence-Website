<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PENDING_REVIEW = 'payment_pending_review';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_FAILED = 'failed';
    public const PAYMENT_REJECTED = 'rejected';
    public const PAYMENT_REFUNDED = 'refunded';
    public const PAYMENT_PARTIAL_REFUND = 'partial_refund';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'placed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentSlips(): HasMany
    {
        return $this->hasMany(PaymentSlip::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }
}
