<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Collection\StoreCollectionRequest;
use App\Http\Requests\Collection\UpdateCollectionRequest;
use App\Http\Requests\DashboardIndexRequest;
use App\Http\Resources\CollectionResource;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CollectionController extends Controller
{
    public function publicIndex(): JsonResponse
    {
        $today = now()->toDateString();

        $collections = Collection::query()
            ->where(fn ($query) => $query
                ->whereNull('start_date')
                ->orWhereDate('start_date', '<=', $today))
            ->where(fn ($query) => $query
                ->whereNull('end_date')
                ->orWhereDate('end_date', '>=', $today))
            ->withCount([
                'products as products_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CollectionResource::collection($collections),
            'message' => 'Lấy danh sách bộ sưu tập thành công.',
        ]);
    }

    public function index(DashboardIndexRequest $request): JsonResponse
    {
        $collections = Collection::query()
            ->with([
                'products' => fn ($query) => $query
                    ->with(['brand', 'category', 'images'])
                    ->withCount('variants')
                    ->orderBy('product_collection.display_order')
                    ->orderBy('products.name'),
            ])
            ->withCount('products')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->trim()->toString();

                $query->where(fn ($searchQuery) => $searchQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => [
                'items' => CollectionResource::collection($collections->getCollection()),
                'pagination' => [
                    'current_page' => $collections->currentPage(),
                    'per_page' => $collections->perPage(),
                    'total' => $collections->total(),
                    'last_page' => $collections->lastPage(),
                ],
            ],
            'message' => 'Lấy danh sách bộ sưu tập quản trị thành công.',
        ]);
    }

    public function store(StoreCollectionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? $this->uniqueSlug($data['name']);

        $collection = Collection::query()->create($data);

        return response()->json([
            'success' => true,
            'data' => new CollectionResource($collection->loadCount('products')),
            'message' => 'Tạo bộ sưu tập thành công.',
        ], 201);
    }

    public function update(UpdateCollectionRequest $request, Collection $collection): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['name']) && ! array_key_exists('slug', $data)) {
            $data['slug'] = $this->uniqueSlug($data['name'], $collection->id);
        }

        $collection->update($data);

        return response()->json([
            'success' => true,
            'data' => new CollectionResource($collection->refresh()->loadCount('products')),
            'message' => 'Cập nhật bộ sưu tập thành công.',
        ]);
    }

    public function destroy(Collection $collection): JsonResponse
    {
        $collection->delete();

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Xóa bộ sưu tập thành công.',
        ]);
    }

    public function attachProduct(Request $request, Collection $collection): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $collection->products()->syncWithoutDetaching([
            $data['product_id'] => [
                'display_order' => $data['display_order'] ?? 0,
            ],
        ]);

        return response()->json([
            'success' => true,
            'data' => new CollectionResource($this->loadCollectionForAdmin($collection->refresh())),
            'message' => 'Đã thêm sản phẩm vào bộ sưu tập.',
        ]);
    }

    public function detachProduct(Collection $collection, Product $product): JsonResponse
    {
        $collection->products()->detach($product->id);

        return response()->json([
            'success' => true,
            'data' => new CollectionResource($this->loadCollectionForAdmin($collection->refresh())),
            'message' => 'Đã gỡ sản phẩm khỏi bộ sưu tập.',
        ]);
    }

    public function updateProductOrder(Request $request, Collection $collection, Product $product): JsonResponse
    {
        $data = $request->validate([
            'display_order' => ['required', 'integer', 'min:0'],
        ]);

        if (! $collection->products()->whereKey($product->id)->exists()) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Sản phẩm chưa thuộc bộ sưu tập này.',
            ], 422);
        }

        $collection->products()->updateExistingPivot($product->id, [
            'display_order' => $data['display_order'],
        ]);

        return response()->json([
            'success' => true,
            'data' => new CollectionResource($this->loadCollectionForAdmin($collection->refresh())),
            'message' => 'Đã cập nhật thứ tự hiển thị sản phẩm.',
        ]);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'collection';
        $slug = $base;
        $counter = 2;

        while (Collection::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function loadCollectionForAdmin(Collection $collection): Collection
    {
        return $collection->load([
            'products' => fn ($query) => $query
                ->with(['brand', 'category', 'images'])
                ->withCount('variants')
                ->orderBy('product_collection.display_order')
                ->orderBy('products.name'),
        ])->loadCount('products');
    }
}
