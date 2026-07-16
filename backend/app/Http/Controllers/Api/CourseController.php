<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\StoreCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\User;
use App\Services\CourseService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function __construct(private readonly CourseService $courseService) {}

    public function index(Request $request): JsonResponse
    {
        $courses = Course::query()
            ->with(['category', 'instructor.role', 'certifications'])
            ->where('status', 'approved')
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $keyword = $request->string('search')->trim()->toString();

                $query->where(fn ($searchQuery) => $searchQuery
                    ->where('title', 'like', "%{$keyword}%")
                    ->orWhereHas('instructor', fn ($instructorQuery) => $instructorQuery
                        ->where('name', 'like', "%{$keyword}%"))
                    ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery
                        ->where('name', 'like', "%{$keyword}%")));
            })
            ->latest('published_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CourseResource::collection($courses),
            'message' => 'Láº¥y danh sÃ¡ch khÃ³a há»c thÃ nh cÃ´ng.',
        ]);
    }

    public function show(Request $request, Course $course): JsonResponse
    {
        $user = $request->user('sanctum');
        $canPreviewUnpublished = $user && (
            $course->instructor_id === $user->id
            || $user->role?->name === 'admin'
        );

        abort_unless($course->status === 'approved' || $canPreviewUnpublished, 404);

        return response()->json([
            'success' => true,
            'data' => new CourseResource($course->load(['category', 'instructor.role', 'certifications'])),
            'message' => 'Láº¥y thÃ´ng tin khÃ³a há»c thÃ nh cÃ´ng.',
        ]);
    }

    public function related(Request $request, Course $course): JsonResponse
    {
        abort_unless($course->status === 'approved', 404);

        $limit = min(max($request->integer('limit', 6), 1), 6);
        $courses = Course::query()
            ->with(['category', 'instructor.role', 'certifications'])
            ->where('status', 'approved')
            ->where('category_id', $course->category_id)
            ->whereKeyNot($course->id)
            ->orderByDesc('rating_avg')
            ->orderByDesc('total_students')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => CourseResource::collection($courses),
            'message' => 'Lấy danh sách khóa học liên quan thành công.',
        ]);
    }

    public function instructorCourses(Request $request, User $instructor): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 6), 1), 6);
        $excludeCourseId = $request->integer('exclude_course_id') ?: null;

        $courses = $instructor->courses()
            ->with(['category', 'instructor.role', 'certifications'])
            ->where('status', 'approved')
            ->when($excludeCourseId, fn ($query) => $query->whereKeyNot($excludeCourseId))
            ->orderByDesc('rating_avg')
            ->orderByDesc('total_students')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => CourseResource::collection($courses->getCollection()),
                'pagination' => [
                    'current_page' => $courses->currentPage(),
                    'last_page' => $courses->lastPage(),
                    'per_page' => $courses->perPage(),
                    'total' => $courses->total(),
                ],
            ],
            'message' => 'Lấy danh sách khóa học của giảng viên thành công.',
        ]);
    }

    public function instructorIndex(Request $request): JsonResponse
    {
        $courses = Course::query()
            ->with(['category', 'instructor.role', 'certifications'])
            ->where('instructor_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => CourseResource::collection($courses),
            'message' => 'Láº¥y danh sÃ¡ch khÃ³a há»c cá»§a giáº£ng viÃªn thÃ nh cÃ´ng.',
        ]);
    }

    public function store(StoreCourseRequest $request): JsonResponse
    {
        $course = $this->courseService->create($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'data' => new CourseResource($course->load(['category', 'instructor.role', 'certifications'])),
            'message' => 'Táº¡o khÃ³a há»c thÃ nh cÃ´ng.',
        ], 201);
    }

    public function update(UpdateCourseRequest $request, Course $course): JsonResponse
    {
        $course = $this->courseService->update($course, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new CourseResource($course->load(['category', 'instructor.role', 'certifications'])),
            'message' => 'Cáº­p nháº­t khÃ³a há»c thÃ nh cÃ´ng.',
        ]);
    }

    public function destroy(Request $request, Course $course): JsonResponse
    {
        if ($course->instructor_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Báº¡n khÃ´ng cÃ³ quyá»n chá»‰nh sá»­a khÃ³a há»c nÃ y.',
            ], 403);
        }

        $this->courseService->delete($course);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'XÃ³a khÃ³a há»c thÃ nh cÃ´ng.',
        ]);
    }

    public function submit(Request $request, Course $course): JsonResponse
    {
        if ($course->instructor_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Báº¡n khÃ´ng cÃ³ quyá»n gá»­i duyá»‡t khÃ³a há»c nÃ y.',
            ], 403);
        }

        try {
            $course = $this->courseService->submitForReview($course);
        } catch (DomainException $exception) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => $exception->getMessage(),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => new CourseResource($course->load(['category', 'instructor.role', 'certifications'])),
            'message' => 'ÄÃ£ gá»­i khÃ³a há»c Ä‘á»ƒ chá» duyá»‡t.',
        ]);
    }
}
