<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function checkout(User $user): Order
    {
        return DB::transaction(function () use ($user) {
            $cart = Cart::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $cart) {
                throw ValidationException::withMessages([
                    'cart' => ['Giỏ hàng đang trống.'],
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

            $courses = Course::query()
                ->whereIn('id', $items->pluck('course_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $unavailableCourse = $items->first(
                fn ($item) => $courses->get($item->course_id)?->status !== 'approved'
            );

            if ($unavailableCourse) {
                throw ValidationException::withMessages([
                    'cart' => ['Giỏ hàng có khóa học không còn khả dụng.'],
                ]);
            }

            $cartCourseIds = $items->pluck('course_id')
                ->map(fn ($courseId): int => (int) $courseId)
                ->sort()
                ->values()
                ->all();

            $pendingOrder = $user->orders()
                ->where('status', 'pending')
                ->with('items:id,order_id,course_id')
                ->lockForUpdate()
                ->latest('id')
                ->get()
                ->first(function (Order $order) use ($cartCourseIds): bool {
                    $orderCourseIds = $order->items->pluck('course_id')
                        ->map(fn ($courseId): int => (int) $courseId)
                        ->sort()
                        ->values()
                        ->all();

                    return $orderCourseIds === $cartCourseIds;
                });

            if ($pendingOrder) {
                return $pendingOrder->load('items.course.category', 'items.course.instructor.role');
            }

            $snapshots = $items->map(fn ($item) => [
                'course_id' => $item->course_id,
                'price' => $courses->get($item->course_id)->final_price,
            ]);

            $order = $user->orders()->create([
                'code' => 'ORD'.now()->format('YmdHis').Str::upper(Str::random(6)),
                'total_amount' => $snapshots->sum(fn ($item) => (float) $item['price']),
                'status' => 'pending',
            ]);

            $order->items()->createMany($snapshots->all());

            return $order->load('items.course.category', 'items.course.instructor.role');
        });
    }
}
