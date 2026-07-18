<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function getCart(User $user): Cart
    {
        return $user->cart()->firstOrCreate();
    }

    public function add(User $user, ProductVariant $variant, int $quantity = 1): Cart
    {
        $variant->loadMissing('product');

        if (! $variant->is_active || $variant->product?->status !== 'active') {
            throw ValidationException::withMessages([
                'product_variant_id' => ['Chỉ có thể thêm sản phẩm đang bán vào giỏ hàng.'],
            ]);
        }

        if ($variant->stock_quantity < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => ['Số lượng trong kho không đủ.'],
            ]);
        }

        $cart = $this->getCart($user);
        $cartItem = $cart->items()->where('product_variant_id', $variant->id)->first();

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $quantity;

            if ($variant->stock_quantity < $newQuantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Số lượng trong kho không đủ.'],
                ]);
            }

            $cartItem->update(['quantity' => $newQuantity]);

            return $cart;
        }

        $cart->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);

        return $cart;
    }

    public function remove(User $user, CartItem $cartItem): Cart
    {
        $cart = $this->getCart($user);

        if ($cartItem->cart_id !== $cart->id) {
            throw ValidationException::withMessages([
                'cart_item' => ['Sản phẩm không thuộc giỏ hàng của bạn.'],
            ]);
        }

        $cartItem->delete();

        return $cart;
    }

    public function updateQuantity(User $user, CartItem $cartItem, int $quantity): Cart
    {
        $cart = $this->getCart($user);

        if ($cartItem->cart_id !== $cart->id) {
            throw ValidationException::withMessages([
                'cart_item' => ['Sản phẩm không thuộc giỏ hàng của bạn.'],
            ]);
        }

        $cartItem->loadMissing('productVariant.product');
        $variant = $cartItem->productVariant;
        $product = $variant?->product;

        if (! $variant || ! $product || ! $variant->is_active || $product->status !== 'active') {
            throw ValidationException::withMessages([
                'product_variant_id' => ['Sản phẩm này không còn khả dụng.'],
            ]);
        }

        if ($variant->stock_quantity < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => ['Số lượng trong kho không đủ.'],
            ]);
        }

        $cartItem->update(['quantity' => $quantity]);

        return $cart;
    }

    public function hasPurchasedProductVariant(User $user, ProductVariant $variant): bool
    {
        return $user->orders()
            ->whereIn('status', ['paid', 'shipping', 'completed'])
            ->whereHas('items', fn ($query) => $query->where('product_variant_id', $variant->id))
            ->exists();
    }
}
