<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DashboardIndexRequest;
use App\Http\Resources\EnrollmentStudentResource;
use App\Models\Course;
use App\Services\InstructorDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstructorDashboardController extends Controller
{
    public function __construct(private readonly InstructorDashboardService $dashboardService) {}

    public function stats(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->stats($request->user()),
            'message' => 'Lấy thống kê giảng viên thành công.',
        ]);
    }

    public function students(DashboardIndexRequest $request, Course $course): JsonResponse
    {
        if ($course->instructor_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Bạn không có quyền xem học viên của khóa học này.',
            ], 403);
        }

        $enrollments = $course->enrollments()
            ->with('user.role')
            ->withCount([
                'lessonProgress as completed_lessons_count' => fn ($query) => $query->where('is_completed', true),
            ])
            ->latest('enrolled_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => [
                'items' => EnrollmentStudentResource::collection($enrollments->getCollection()),
                'pagination' => [
                    'current_page' => $enrollments->currentPage(),
                    'per_page' => $enrollments->perPage(),
                    'total' => $enrollments->total(),
                    'last_page' => $enrollments->lastPage(),
                ],
            ],
            'message' => 'Lấy danh sách học viên thành công.',
        ]);
    }
}
