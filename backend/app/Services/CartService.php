<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Course;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function getCart(User $user): Cart
    {
        return $user->cart()->firstOrCreate();
    }

    public function add(User $user, Course $course): Cart
    {
        if ($course->status !== 'approved') {
            throw ValidationException::withMessages([
                'course_id' => ['Chỉ có thể thêm khóa học đã được duyệt vào giỏ hàng.'],
            ]);
        }

        if ($this->ownsCourse($user, $course)) {
            throw ValidationException::withMessages([
                'course_id' => ['Bạn đã sở hữu khóa học này.'],
            ]);
        }

        $cart = $this->getCart($user);

        if ($cart->items()->where('course_id', $course->id)->exists()) {
            throw ValidationException::withMessages([
                'course_id' => ['Khóa học đã có trong giỏ hàng.'],
            ]);
        }

        $cart->items()->create([
            'course_id' => $course->id,
            'price_snapshot' => $course->final_price,
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

    private function ownsCourse(User $user, Course $course): bool
    {
        if ($user->enrollments()->where('course_id', $course->id)->exists()) {
            return true;
        }

        return $user->orders()
            ->where('status', 'paid')
            ->whereHas('items', fn ($query) => $query->where('course_id', $course->id))
            ->exists();
    }
}
