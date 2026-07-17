<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
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
        $variant = ProductVariant::query()->findOrFail($request->validated('product_variant_id'));
        $cart = $this->cartService->add($request->user(), $variant, $request->integer('quantity', 1));

        return $this->cartResponse($cart, 'Thêm sản phẩm vào giỏ hàng thành công.', 201);
    }

    public function destroy(Request $request, CartItem $cartItem): JsonResponse
    {
        $cart = $this->cartService->remove($request->user(), $cartItem);

        return $this->cartResponse($cart, 'Xóa sản phẩm khỏi giỏ hàng thành công.');
    }

    private function cartResponse(Cart $cart, string $message, int $status = 200): JsonResponse
    {
        $cart->load([
            'items.productVariant.product.brand',
            'items.productVariant.product.category',
        ]);

        return response()->json([
            'success' => true,
            'data' => new CartResource($cart),
            'message' => $message,
        ], $status);
    }
}
