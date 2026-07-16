<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PublicLandingController extends Controller
{
    public function stats(): JsonResponse
    {
        $stats = Cache::remember('landing.public_stats', now()->addHour(), function (): array {
            $averageRating = Review::query()->avg('rating');

            if ($averageRating === null) {
                $averageRating = Course::query()
                    ->where('status', 'approved')
                    ->where('rating_avg', '>', 0)
                    ->avg('rating_avg');
            }

            return [
                'total_courses' => Course::query()->where('status', 'approved')->count(),
                'total_students' => Enrollment::query()->distinct('user_id')->count('user_id'),
                'total_instructors' => User::query()
                    ->whereHas('role', fn ($query) => $query->where('name', 'instructor'))
                    ->count(),
                'avg_rating' => round((float) ($averageRating ?? 0), 1),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Lấy thống kê landing thành công.',
        ]);
    }

    public function featuredInstructors(): JsonResponse
    {
        $instructors = Cache::remember('landing.featured_instructors', now()->addHour(), function () {
            return User::query()
                ->whereHas('role', fn ($query) => $query->where('name', 'instructor'))
                ->with([
                    'courses' => fn ($query) => $query
                        ->where('status', 'approved')
                        ->with(['category:id,name'])
                        ->select([
                            'id',
                            'instructor_id',
                            'category_id',
                            'title',
                            'rating_avg',
                            'total_students',
                            'status',
                        ])
                        ->withCount('enrollments'),
                ])
                ->get()
                ->map(function (User $instructor): array {
                    $approvedCourses = $instructor->courses;
                    $primaryCategory = $approvedCourses
                        ->pluck('category.name')
                        ->filter()
                        ->countBy()
                        ->sortDesc()
                        ->keys()
                        ->first();

                    $totalStudents = $approvedCourses->sum(
                        fn (Course $course) => max((int) $course->total_students, (int) $course->enrollments_count)
                    );

                    return [
                        'id' => $instructor->id,
                        'name' => $instructor->name,
                        'avatar_url' => $instructor->avatar_url,
                        'specialty' => $primaryCategory ?: 'Software Engineering',
                        'courses_count' => $approvedCourses->count(),
                        'total_students' => $totalStudents,
                        'rating_avg' => round((float) $approvedCourses->avg('rating_avg'), 1),
                    ];
                })
                ->filter(fn (array $instructor) => $instructor['courses_count'] > 0)
                ->sort(function (array $first, array $second): int {
                    return [
                        $second['total_students'],
                        $second['rating_avg'],
                        $second['courses_count'],
                    ] <=> [
                        $first['total_students'],
                        $first['rating_avg'],
                        $first['courses_count'],
                    ];
                })
                ->take(4)
                ->values();
        });

        return response()->json([
            'success' => true,
            'data' => $instructors,
            'message' => 'Lấy danh sách giảng viên nổi bật thành công.',
        ]);
    }
}
