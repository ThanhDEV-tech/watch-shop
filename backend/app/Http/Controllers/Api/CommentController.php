<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\DeleteCommentRequest;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\ViewLessonCommentsRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Lesson;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    public function __construct(private readonly CommentService $commentService) {}

    public function index(ViewLessonCommentsRequest $request, Lesson $lesson): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 15), 1), 50);
        $comments = Comment::query()
            ->withTrashed()
            ->where('lesson_id', $lesson->id)
            ->whereNull('parent_id')
            ->with(['user', 'repliesRecursive'])
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => CommentResource::collection($comments->getCollection()),
                'pagination' => [
                    'current_page' => $comments->currentPage(),
                    'per_page' => $comments->perPage(),
                    'total' => $comments->total(),
                    'last_page' => $comments->lastPage(),
                ],
            ],
            'message' => 'Lấy danh sách bình luận thành công.',
        ]);
    }

    public function store(StoreCommentRequest $request, Lesson $lesson): JsonResponse
    {
        $comment = $this->commentService->create($request->user(), $lesson, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new CommentResource($comment),
            'message' => 'Gửi bình luận thành công.',
        ], 201);
    }

    public function destroy(DeleteCommentRequest $request, Comment $comment): JsonResponse
    {
        $keptAsDeletedPlaceholder = $this->commentService->delete($comment);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => $keptAsDeletedPlaceholder
                ? 'Bình luận đã được ẩn và các phản hồi được giữ lại.'
                : 'Xóa bình luận thành công.',
        ]);
    }
}
