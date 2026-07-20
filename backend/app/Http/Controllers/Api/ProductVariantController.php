<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductVariant\StoreProductVariantRequest;
use App\Http\Requests\ProductVariant\UpdateProductVariantRequest;
use App\Http\Resources\ProductVariantResource;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $variants = ProductVariant::query()
            ->with('product.brand')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->trim()->toString();

                $query->where(fn ($searchQuery) => $searchQuery
                    ->where('sku', 'like', "%{$search}%")
                    ->orWhere('strap_color', 'like', "%{$search}%")
                    ->orWhere('dial_color', 'like', "%{$search}%"));
            })
            ->when($request->filled('product_id'), fn ($query) => $query->where('product_id', $request->integer('product_id')))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => [
                'items' => ProductVariantResource::collection($variants->getCollection()),
                'pagination' => [
                    'current_page' => $variants->currentPage(),
                    'per_page' => $variants->perPage(),
                    'total' => $variants->total(),
                    'last_page' => $variants->lastPage(),
                ],
            ],
            'message' => 'Lấy danh sách biến thể sản phẩm quản trị thành công.',
        ]);
    }

    public function show(ProductVariant $productVariant): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new ProductVariantResource($productVariant->load('product.brand')),
            'message' => 'Lấy thông tin biến thể sản phẩm thành công.',
        ]);
    }

    public function store(StoreProductVariantRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($this->optionCombinationExists($data)) {
            return $this->duplicateCombinationResponse();
        }

        $variant = ProductVariant::query()->create($data);

        return response()->json([
            'success' => true,
            'data' => new ProductVariantResource($variant->refresh()->load('product.brand')),
            'message' => 'Tạo biến thể sản phẩm thành công.',
        ], 201);
    }

    public function update(UpdateProductVariantRequest $request, ProductVariant $productVariant): JsonResponse
    {
        $data = $request->validated();
        $merged = array_merge($productVariant->only([
            'product_id',
            'strap_color',
            'dial_color',
            'diameter_mm',
            'movement_type',
        ]), $data);

        if ($this->optionCombinationExists($merged, $productVariant->id)) {
            return $this->duplicateCombinationResponse();
        }

        $productVariant->update($data);

        return response()->json([
            'success' => true,
            'data' => new ProductVariantResource($productVariant->refresh()->load('product.brand')),
            'message' => 'Cập nhật biến thể sản phẩm thành công.',
        ]);
    }

    public function destroy(ProductVariant $productVariant): JsonResponse
    {
        $productVariant->delete();

        return response()->json([
            'success' => true,
            'data' => [
                'errors' => [
                    'option_combination' => [
                        'Tổ hợp dây, mặt, đường kính và bộ máy này đã tồn tại.',
                    ],
                ],
            ],
            'message' => 'Xóa biến thể sản phẩm thành công.',
        ]);
    }

    /** @param array<string, mixed> $data */
    private function optionCombinationExists(array $data, ?int $ignoreId = null): bool
    {
        return ProductVariant::query()
            ->where('product_id', $data['product_id'])
            ->where('strap_color', $data['strap_color'])
            ->where('dial_color', $data['dial_color'])
            ->where('diameter_mm', $data['diameter_mm'])
            ->where('movement_type', $data['movement_type'])
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    private function duplicateCombinationResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => 'Biến thể sản phẩm với tổ hợp dây, mặt, đường kính và bộ máy này đã tồn tại.',
        ], 422);
    }
}
