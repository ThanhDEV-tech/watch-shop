<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    /** @param array<string, mixed> $data */
    public function checkout(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $cart = Cart::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $cart) {
                throw ValidationException::withMessages([
                    'cart' => ['Giỏ hàng đang trống.'],
                ]);
            }

            $shippingZone = ShippingZone::query()
                ->whereKey($data['shipping_zone_id'])
                ->lockForUpdate()
                ->first();

            if (! $shippingZone || ! $shippingZone->is_active) {
                throw ValidationException::withMessages([
                    'shipping_zone_id' => ['Khu vực giao hàng không khả dụng.'],
                ]);
            }

            $items = $cart->items()
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => ['Giỏ hàng đang trống.'],
                ]);
            }

            $variants = ProductVariant::query()
                ->with('product.brand')
                ->whereIn('id', $items->pluck('product_variant_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $snapshots = $items->map(function ($item) use ($variants): array {
                $variant = $variants->get($item->product_variant_id);
                $product = $variant?->product;

                if (! $variant || ! $product || ! $variant->is_active || $product->status !== 'active') {
                    throw ValidationException::withMessages([
                        'cart' => ['Giỏ hàng có sản phẩm không còn khả dụng.'],
                    ]);
                }

                if ($variant->stock_quantity < $item->quantity) {
                    throw ValidationException::withMessages([
                        'cart' => ["Sản phẩm {$product->name} không đủ tồn kho."],
                    ]);
                }

                $unitPrice = (string) $variant->final_price;
                $quantity = (int) $item->quantity;
                $lineTotal = number_format((float) $unitPrice * $quantity, 2, '.', '');

                return [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $product->name,
                    'brand_name' => $product->brand?->name,
                    'sku' => $variant->sku,
                    'strap_color' => $variant->strap_color,
                    'dial_color' => $variant->dial_color,
                    'diameter_mm' => $variant->diameter_mm,
                    'movement_type' => $variant->movement_type,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                    'thumbnail_url' => $variant->image ?: $product->thumbnail,
                ];
            });

            $subtotalAmount = $snapshots->sum(fn ($item): float => (float) $item['line_total']);
            $shippingFee = (string) $shippingZone->fee;
            $totalAmount = number_format($subtotalAmount + (float) $shippingFee, 2, '.', '');

            $order = $user->orders()->create([
                'code' => 'ORD'.now()->format('YmdHis').Str::upper(Str::random(6)),
                'receiver_name' => $data['receiver_name'],
                'receiver_phone' => $data['receiver_phone'],
                'shipping_address' => $data['shipping_address'],
                'shipping_note' => $data['shipping_note'] ?? null,
                'shipping_zone_name' => $shippingZone->name,
                'shipping_fee' => $shippingFee,
                'subtotal_amount' => number_format($subtotalAmount, 2, '.', ''),
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]);

            $order->items()->createMany($snapshots->all());

            return $order->load('items.product', 'items.productVariant');
        });
    }
}
