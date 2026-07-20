<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function publicIndex(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with([
                'brand',
                'category',
                'collections',
                'images',
                'variants' => fn ($query) => $query->where('is_active', true),
            ])
            ->where('status', 'active')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->trim()->toString();

                $query->where(fn ($searchQuery) => $searchQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhereHas('brand', fn ($brandQuery) => $brandQuery->where('name', 'like', "%{$search}%")));
            })
            ->when($request->filled('gender_target'), fn ($query) => $query
                ->where('gender_target', $request->string('gender_target')))
            ->when($request->filled('brand_id'), fn ($query) => $query
                ->where('brand_id', $request->integer('brand_id')))
            ->when($request->filled('brand'), fn ($query) => $query
                ->whereHas('brand', fn ($brandQuery) => $brandQuery
                    ->where('slug', $request->string('brand'))))
            ->when($request->filled('category_id'), fn ($query) => $query
                ->where('category_id', $request->integer('category_id')))
            ->when($request->filled('category'), fn ($query) => $query
                ->whereHas('category', fn ($categoryQuery) => $categoryQuery
                    ->where('slug', $request->string('category'))))
            ->when($request->filled('collection_id'), fn ($query) => $query
                ->whereHas('collections', fn ($collectionQuery) => $collectionQuery
                    ->where('collections.id', $request->integer('collection_id'))))
            ->when($request->filled('collection'), fn ($query) => $query
                ->whereHas('collections', fn ($collectionQuery) => $collectionQuery
                    ->where('collections.slug', $request->string('collection'))))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 12));

        return response()->json([
            'success' => true,
            'data' => [
                'items' => ProductResource::collection($products->getCollection()),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'last_page' => $products->lastPage(),
                ],
            ],
            'message' => 'Lấy danh sách sản phẩm thành công.',
        ]);
    }

    public function publicShow(string $slug): JsonResponse
    {
        $relations = [
            'brand',
            'category',
            'collections',
            'variants' => fn ($query) => $query->where('is_active', true),
        ];

        if (method_exists(Product::class, 'images')) {
            $relations[] = 'images';
        }

        if (method_exists(Product::class, 'productImages')) {
            $relations[] = 'productImages';
        }

        $product = Product::query()
            ->with($relations)
            ->where('status', 'active')
            ->where('slug', $slug)
            ->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Không tìm thấy sản phẩm.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
            'message' => 'Lấy chi tiết sản phẩm thành công.',
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with([
                'brand',
                'category',
                'collections',
                'images',
                'variants' => fn ($query) => $query->where('is_active', true),
            ])
            ->withCount('variants')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->trim()->toString();

                $query->where(fn ($searchQuery) => $searchQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%"));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('brand_id'), fn ($query) => $query->where('brand_id', $request->integer('brand_id')))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => [
                'items' => ProductResource::collection($products->getCollection()),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'last_page' => $products->lastPage(),
                ],
            ],
            'message' => 'Lấy danh sách sản phẩm quản trị thành công.',
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new ProductResource($product->load(['brand', 'category', 'variants', 'collections', 'images'])),
            'message' => 'Lấy thông tin sản phẩm thành công.',
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $collectionIds = Arr::pull($data, 'collection_ids', []);
        $productImages = Arr::pull($data, 'product_images', []);
        $data['slug'] = $data['slug'] ?? $this->uniqueSlug($data['name']);

        $product = DB::transaction(function () use ($data, $collectionIds, $productImages): Product {
            $product = Product::query()->create($data);
            $product->collections()->sync($collectionIds);
            $this->syncImages($product, $productImages);

            return $product;
        });

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product->load(['brand', 'category', 'collections', 'images', 'variants'])),
            'message' => 'Tạo sản phẩm thành công.',
        ], 201);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();
        $hasCollectionIds = array_key_exists('collection_ids', $data);
        $collectionIds = Arr::pull($data, 'collection_ids', []);
        $hasProductImages = array_key_exists('product_images', $data);
        $productImages = Arr::pull($data, 'product_images', []);

        if (isset($data['name']) && ! array_key_exists('slug', $data)) {
            $data['slug'] = $this->uniqueSlug($data['name'], $product->id);
        }

        DB::transaction(function () use ($product, $data, $hasCollectionIds, $collectionIds, $hasProductImages, $productImages): void {
            $product->update($data);

            if ($hasCollectionIds) {
                $product->collections()->sync($collectionIds);
            }

            if ($hasProductImages) {
                $this->syncImages($product, $productImages);
            }
        });

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product->refresh()->load(['brand', 'category', 'collections', 'images', 'variants'])),
            'message' => 'Cập nhật sản phẩm thành công.',
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Xóa sản phẩm thành công.',
        ]);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $counter = 2;

        while (Product::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /** @param array<int, array<string, mixed>> $images */
    private function syncImages(Product $product, array $images): void
    {
        $normalizedImages = collect($images)
            ->filter(fn ($image) => filled($image['image_path'] ?? null))
            ->values();

        $primaryIndex = $normalizedImages->search(fn ($image) => (bool) ($image['is_primary'] ?? false));

        if ($primaryIndex === false && $normalizedImages->isNotEmpty()) {
            $primaryIndex = 0;
        }

        $product->images()->delete();

        $normalizedImages->each(function (array $image, int $index) use ($product, $primaryIndex): void {
            $product->images()->create([
                'image_path' => $image['image_path'],
                'alt_text' => $image['alt_text'] ?? $product->name,
                'display_order' => $image['display_order'] ?? $index,
                'is_primary' => $index === $primaryIndex,
            ]);
        });
    }
}
