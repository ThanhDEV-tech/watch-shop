<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Requests\DashboardIndexRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->withCount([
                'products as products_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
            'message' => 'Lấy danh sách danh mục thành công.',
        ]);
    }

    public function adminIndex(DashboardIndexRequest $request): JsonResponse
    {
        $categories = Category::query()
            ->withCount('products')
            ->when($request->filled('search'), fn ($query) => $query->where(
                fn ($searchQuery) => $searchQuery
                    ->where('name', 'like', '%'.$request->string('search')->trim().'%')
                    ->orWhere('slug', 'like', '%'.$request->string('search')->trim().'%')
            ))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => [
                'items' => CategoryResource::collection($categories->getCollection()),
                'pagination' => [
                    'current_page' => $categories->currentPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total(),
                    'last_page' => $categories->lastPage(),
                ],
            ],
            'message' => 'Lấy danh sách danh mục quản trị thành công.',
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? $this->uniqueSlug($data['name']);
        $category = Category::query()->create($data);

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
            'message' => 'Tạo danh mục thành công.',
        ], 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['name']) && ! array_key_exists('slug', $data)) {
            $data['slug'] = $this->uniqueSlug($data['name'], $category->id);
        }

        $category->update($data);

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category->refresh()),
            'message' => 'Cập nhật danh mục thành công.',
        ]);
    }

    public function toggleActive(Category $category): JsonResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category->refresh()),
            'message' => 'Cập nhật trạng thái danh mục thành công.',
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Không thể xóa danh mục đang có sản phẩm.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Xóa danh mục thành công.',
        ]);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $counter = 2;

        while (Category::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
