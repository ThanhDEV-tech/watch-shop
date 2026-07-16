<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Course;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function show(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart($request->user());

        return $this->cartResponse($cart, 'Lấy giỏ hàng thành công.');
    }

    public function store(AddCartItemRequest $request): JsonResponse
    {
        $course = Course::query()->findOrFail($request->validated('course_id'));
        $cart = $this->cartService->add($request->user(), $course);

        return $this->cartResponse($cart, 'Thêm khóa học vào giỏ hàng thành công.', 201);
    }

    public function destroy(Request $request, CartItem $cartItem): JsonResponse
    {
        $cart = $this->cartService->remove($request->user(), $cartItem);

        return $this->cartResponse($cart, 'Xóa khóa học khỏi giỏ hàng thành công.');
    }

    private function cartResponse(Cart $cart, string $message, int $status = 200): JsonResponse
    {
        $cart->load([
            'items.course.category',
            'items.course.instructor.role',
        ]);

        return response()->json([
            'success' => true,
            'data' => new CartResource($cart),
            'message' => $message,
        ], $status);
    }
}
