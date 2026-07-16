<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\AdminCourseIndexRequest;
use App\Http\Requests\Course\RejectCourseRequest;
use App\Http\Requests\Course\UpdateCourseStatusRequest;
use App\Http\Resources\AdminCourseResource;
use App\Http\Resources\CourseResource;
use App\Mail\CourseReviewedMail;
use App\Models\Course;
use App\Services\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;

class AdminCourseController extends Controller
{
    public function __construct(private readonly CourseService $courseService) {}

    public function index(AdminCourseIndexRequest $request): JsonResponse
    {
        $courses = Course::query()
            ->with(['category', 'instructor.role'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->trim()->toString();
                $query->where(fn ($searchQuery) => $searchQuery
                    ->where('title', 'like', "%{$search}%")
                    ->orWhereHas('instructor', fn ($instructorQuery) => $instructorQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")));
            })
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $this->paginatedData($courses, AdminCourseResource::collection($courses->getCollection())),
            'message' => 'Lấy danh sách khóa học quản trị thành công.',
        ]);
    }

    public function show(Course $course): JsonResponse
    {
        $course->load([
            'category',
            'instructor.role',
            'chapters' => fn ($query) => $query->orderBy('position')->orderBy('id'),
            'chapters.lessons' => fn ($query) => $query->orderBy('position')->orderBy('id'),
        ]);

        return response()->json([
            'success' => true,
            'data' => new AdminCourseResource($course),
            'message' => 'Lấy chi tiết khóa học quản trị thành công.',
        ]);
    }

    public function approve(Course $course): JsonResponse
    {
        $course = $this->courseService->changeStatus($course, 'approved', null);
        $this->queueReviewEmail($course);

        return $this->statusResponse($course, 'Duyệt khóa học thành công.');
    }

    public function reject(RejectCourseRequest $request, Course $course): JsonResponse
    {
        $course = $this->courseService->changeStatus($course, 'rejected', $request->validated('reason'));
        $this->queueReviewEmail($course);

        return $this->statusResponse($course, 'Từ chối khóa học thành công.');
    }

    public function updateStatus(UpdateCourseStatusRequest $request, Course $course): JsonResponse
    {
        $course = $this->courseService->changeStatus(
            $course,
            $request->validated('status'),
            $request->validated('reject_reason')
        );

        return response()->json([
            'success' => true,
            'data' => new CourseResource($course->load(['category', 'instructor.role'])),
            'message' => 'Cập nhật trạng thái khóa học thành công.',
        ]);
    }

    private function statusResponse(Course $course, string $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new AdminCourseResource($course->load(['category', 'instructor.role'])),
            'message' => $message,
        ]);
    }

    private function queueReviewEmail(Course $course): void
    {
        $course->loadMissing('instructor');
        Mail::to($course->instructor->email)->queue(new CourseReviewedMail($course));
    }

    /** @return array<string, mixed> */
    private function paginatedData(LengthAwarePaginator $paginator, mixed $items): array
    {
        return [
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }
}
