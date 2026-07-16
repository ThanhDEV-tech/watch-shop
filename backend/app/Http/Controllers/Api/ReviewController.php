<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\DeleteReviewRequest;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Course;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviewService) {}

    public function index(Request $request, Course $course): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 15), 1), 50);
        $reviews = $course->reviews()
            ->with('user')
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage);

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
            ],
            'message' => 'Lấy danh sách đánh giá thành công.',
        ]);
    }

    public function store(StoreReviewRequest $request, Course $course): JsonResponse
    {
        $review = $this->reviewService->upsert($request->user(), $course, $request->validated());
        $status = $review->wasRecentlyCreated ? 201 : 200;

        return response()->json([
            'success' => true,
            'data' => new ReviewResource($review),
            'message' => $review->wasRecentlyCreated
                ? 'Đánh giá khóa học thành công.'
                : 'Cập nhật đánh giá thành công.',
        ], $status);
    }

    public function destroy(DeleteReviewRequest $request, Course $course, Review $review): JsonResponse
    {
        abort_unless($review->course_id === $course->id, 404);
        $this->reviewService->delete($review);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Xóa đánh giá thành công.',
        ]);
    }
}
