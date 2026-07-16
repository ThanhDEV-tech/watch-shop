<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use App\Http\Resources\LessonResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\CourseProgressService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearningController extends Controller
{
    public function __construct(private readonly CourseProgressService $courseProgressService) {}

    public function myCourses(Request $request): JsonResponse
    {
        $enrollments = $request->user()
            ->enrollments()
            ->with(['course.category', 'course.instructor.role'])
            ->latest('enrolled_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => EnrollmentResource::collection($enrollments),
            'message' => 'Lấy danh sách khóa học đã đăng ký thành công.',
        ]);
    }

    /** @throws AuthorizationException */
    public function showLesson(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        $lesson->loadMissing('chapter');
        abort_unless($lesson->chapter->course_id === $course->id, 404);

        $user = $request->user();
        $canPreviewUnpublished = $course->instructor_id === $user->id
            || $user->role?->name === 'admin';

        abort_unless($course->status === 'approved' || $canPreviewUnpublished, 404);

        if ($canPreviewUnpublished) {
            return response()->json([
                'success' => true,
                'data' => new LessonResource($lesson),
                'message' => 'Lấy chi tiết bài học thành công.',
            ]);
        }

        $isEnrolled = $user
            ->enrollments()
            ->where('course_id', $course->id)
            ->exists();

        if (! $isEnrolled && ! $lesson->is_free_preview) {
            return $this->forbiddenResponse();
        }

        return response()->json([
            'success' => true,
            'data' => new LessonResource($lesson),
            'message' => 'Lấy chi tiết bài học thành công.',
        ]);
    }

    /** @throws AuthorizationException */
    public function completeLesson(Request $request, Lesson $lesson): JsonResponse
    {
        try {
            $enrollment = $this->courseProgressService->completeLesson($request->user(), $lesson);
        } catch (AuthorizationException) {
            return $this->forbiddenResponse();
        }

        return response()->json([
            'success' => true,
            'data' => new EnrollmentResource($enrollment->load(['course.category', 'course.instructor.role'])),
            'message' => 'Đánh dấu hoàn thành bài học thành công.',
        ]);
    }

    private function forbiddenResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => 'Bạn chưa đăng ký khóa học này.',
        ], 403);
    }
}
