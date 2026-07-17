<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Collection\StoreCollectionRequest;
use App\Http\Requests\Collection\UpdateCollectionRequest;
use App\Http\Requests\DashboardIndexRequest;
use App\Http\Resources\CollectionResource;
use App\Models\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CollectionController extends Controller
{
    public function publicIndex(): JsonResponse
    {
        $collections = Collection::query()
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
            'data' => new CollectionResource($collection),
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
            'data' => new CollectionResource($collection->refresh()),
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
}
