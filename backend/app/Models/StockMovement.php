<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    public const TYPES = [
        'admin_adjustment',
        'order_paid',
        'refund_adjustment',
    ];

    protected $fillable = [
        'product_variant_id',
        'type',
        'quantity_change',
        'stock_after',
        'order_id',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'product_variant_id' => 'integer',
            'quantity_change' => 'integer',
            'stock_after' => 'integer',
            'order_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
