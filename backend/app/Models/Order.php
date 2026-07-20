<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const STATUSES = [
        'pending',
        'paid',
        'paid_stock_issue',
        'failed',
        'cancelled',
        'shipping',
        'completed',
        'refunded',
    ];

    protected $fillable = [
        'code',
        'vnpay_create_date',
        'user_id',
        'receiver_name',
        'receiver_phone',
        'shipping_address',
        'shipping_note',
        'shipping_zone_name',
        'shipping_fee',
        'subtotal_amount',
        'total_amount',
        'status',
        'paid_at',
        'refunded_at',
        'refund_note',
    ];

    protected function casts(): array
    {
        return [
            'shipping_fee' => 'decimal:2',
            'subtotal_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function vnpayTransactions(): HasMany
    {
        return $this->hasMany(VnpayTransaction::class);
    }
}
