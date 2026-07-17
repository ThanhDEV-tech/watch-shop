<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    use HasFactory;

    public const MOVEMENT_TYPES = ['quartz', 'automatic'];

    public const COLORS = [
        'Đen',
        'Bạc',
        'Vàng gold',
        'Vàng rose',
        'Trắng',
        'Xanh navy',
        'Nâu',
    ];

    protected $fillable = [
        'product_id',
        'sku',
        'strap_color',
        'dial_color',
        'diameter_mm',
        'movement_type',
        'price',
        'discount_price',
        'stock_quantity',
        'image',
        'is_active',
    ];

    protected $appends = ['final_price'];

    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'diameter_mm' => 'integer',
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (ProductVariant $variant): void {
            if ($variant->sku) {
                return;
            }

            $variant->forceFill(['sku' => $variant->generateSku()])->saveQuietly();
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    protected function finalPrice(): Attribute
    {
        return Attribute::get(fn (): string => $this->discount_price ?? $this->price);
    }

    public function generateSku(): string
    {
        return implode('-', [
            'WAT',
            $this->product_id,
            $this->skuPart($this->strap_color),
            $this->skuPart($this->dial_color),
            $this->diameter_mm,
            Str::upper($this->movement_type),
        ]);
    }

    private function skuPart(string $value): string
    {
        return Str::upper(Str::slug(Str::ascii($value), ''));
    }
}
