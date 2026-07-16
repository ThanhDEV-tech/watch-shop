<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Certification\StoreCertificationRequest;
use App\Http\Requests\Certification\UpdateCertificationRequest;
use App\Http\Requests\DashboardIndexRequest;
use App\Http\Resources\CertificationResource;
use App\Http\Resources\CourseResource;
use App\Models\Certification;
use App\Models\Enrollment;
use App\Services\CertificationBadgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class CertificationController extends Controller
{
    public function __construct(private readonly CertificationBadgeService $badgeService) {}

    public function index(): JsonResponse
    {
        $certifications = Certification::query()
            ->withCount([
                'courses as courses_count' => fn ($query) => $query->where('status', 'approved'),
            ])
            ->orderBy('provider')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CertificationResource::collection($certifications),
            'message' => 'Lấy danh sách chứng chỉ thành công.',
        ]);
    }

    public function adminIndex(DashboardIndexRequest $request): JsonResponse
    {
        $certifications = Certification::query()
            ->withCount('courses')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->trim()->toString();

                $query->where(fn ($searchQuery) => $searchQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('provider', 'like', "%{$search}%"));
            })
            ->orderBy('provider')
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => [
                'items' => CertificationResource::collection($certifications->getCollection()),
                'pagination' => [
                    'current_page' => $certifications->currentPage(),
                    'per_page' => $certifications->perPage(),
                    'total' => $certifications->total(),
                    'last_page' => $certifications->lastPage(),
                ],
            ],
            'message' => 'Lấy danh sách chứng chỉ quản trị thành công.',
        ]);
    }

    public function store(StoreCertificationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['accent_color'] = $data['accent_color'] ?? '#FF6B4A';
        $data['icon'] = $data['icon'] ?? 'verified';

        $certification = Certification::query()->create($data);

        return response()->json([
            'success' => true,
            'data' => new CertificationResource($certification->loadCount('courses')),
            'message' => 'Tạo chứng chỉ thành công.',
        ], 201);
    }

    public function update(UpdateCertificationRequest $request, Certification $certification): JsonResponse
    {
        $certification->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => new CertificationResource($certification->refresh()->loadCount('courses')),
            'message' => 'Cập nhật chứng chỉ thành công.',
        ]);
    }

    public function destroy(Certification $certification): JsonResponse
    {
        if ($certification->courses()->exists()) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Không thể xóa chứng chỉ đang có khóa học liên quan.',
            ], 422);
        }

        $certification->delete();

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Xóa chứng chỉ thành công.',
        ]);
    }

    public function regenerateBadge(Certification $certification): JsonResponse
    {
        try {
            $this->badgeService->generate($certification);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Không thể sinh lại badge chứng chỉ. Vui lòng thử lại sau.',
            ], 502);
        }

        return response()->json([
            'success' => true,
            'data' => new CertificationResource($certification->refresh()->loadCount('courses')),
            'message' => 'Sinh lại badge chứng chỉ thành công.',
        ]);
    }

    public function show(Request $request, Certification $certification): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 12), 1), 24);

        $courses = $certification->courses()
            ->where('status', 'approved')
            ->with(['category', 'instructor.role', 'certifications'])
            ->latest('published_at')
            ->paginate($perPage);

        $allRelatedCourses = $certification->courses()
            ->where('status', 'approved')
            ->with(['category', 'instructor.role'])
            ->withCount('enrollments')
            ->get();

        $relatedCourseIds = $allRelatedCourses->pluck('id');

        $totalStudents = $relatedCourseIds->isEmpty()
            ? 0
            : Enrollment::query()
                ->whereIn('course_id', $relatedCourseIds)
                ->distinct('user_id')
                ->count('user_id');

        $instructors = $allRelatedCourses
            ->filter(fn ($course) => $course->instructor)
            ->groupBy('instructor_id')
            ->map(function ($instructorCourses) use ($request) {
                $instructor = $instructorCourses->first()->instructor;
                $topCategory = $instructorCourses
                    ->pluck('category.name')
                    ->filter()
                    ->countBy()
                    ->sortDesc()
                    ->keys()
                    ->first();

                return [
                    'id' => $instructor->id,
                    'name' => $instructor->name,
                    'avatar_url' => $instructor->resolveAvatarUrl($request->getSchemeAndHttpHost()),
                    'specialty' => $topCategory,
                    'courses_count' => $instructorCourses->count(),
                    'total_students' => (int) $instructorCourses->sum(fn ($course) => $course->enrollments_count ?: $course->total_students),
                    'rating_avg' => round((float) $instructorCourses->avg('rating_avg'), 1),
                ];
            })
            ->sortByDesc(fn ($instructor) => $instructor['total_students'] * 10 + $instructor['rating_avg'])
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'certification' => new CertificationResource($certification->loadCount([
                    'courses as courses_count' => fn ($query) => $query->where('status', 'approved'),
                ])),
                'stats' => [
                    'total_courses' => $courses->total(),
                    'total_students' => $totalStudents,
                ],
                'instructors' => $instructors,
                'courses' => [
                    'items' => CourseResource::collection($courses->getCollection())->resolve($request),
                    'pagination' => [
                        'current_page' => $courses->currentPage(),
                        'last_page' => $courses->lastPage(),
                        'per_page' => $courses->perPage(),
                        'total' => $courses->total(),
                    ],
                ],
            ],
            'message' => 'Lấy thông tin chứng chỉ thành công.',
        ]);
    }
}
