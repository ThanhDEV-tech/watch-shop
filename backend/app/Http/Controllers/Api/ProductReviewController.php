<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviewService) {}

    public function index(Request $request, Product $product): JsonResponse
    {
        $reviews = Review::query()
            ->with('user')
            ->where('product_id', $product->id)
            ->latest()
            ->paginate($request->integer('per_page', 8));

        /** @var User|null $user */
        $user = auth('sanctum')->user();
        $existingReview = $user
            ? Review::query()
                ->with('user')
                ->where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->first()
            : null;

        return response()->json([
            'success' => true,
            'data' => [
                'items' => ReviewResource::collection($reviews->getCollection()),
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                    'last_page' => $reviews->lastPage(),
                ],
                'summary' => [
                    'rating_avg' => round((float) ($product->reviews()->avg('rating') ?? 0), 2),
                    'reviews_count' => $product->reviews()->count(),
                ],
                'can_review' => $user ? $this->reviewService->canReviewProduct($user, $product) : false,
                'existing_review' => $existingReview ? new ReviewResource($existingReview) : null,
            ],
            'message' => 'Lấy đánh giá sản phẩm thành công.',
        ]);
    }

    public function store(StoreReviewRequest $request, Product $product): JsonResponse
    {
        $review = $this->reviewService->upsert($request->user(), $product, $request->validated());

        return response()->json([
            'success' => true,
            'data' => [
                'review' => new ReviewResource($review),
                'rating_avg' => $product->refresh()->rating_avg,
                'reviews_count' => $product->reviews()->count(),
            ],
            'message' => 'Cảm ơn bạn đã đánh giá sản phẩm.',
        ], 201);
    }
}
