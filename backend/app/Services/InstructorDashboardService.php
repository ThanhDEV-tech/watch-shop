<?php

namespace App\Services;

use App\Models\Course;
use App\Models\OrderItem;
use App\Models\User;

class InstructorDashboardService
{
    /** @return array<string, mixed> */
    public function stats(User $instructor): array
    {
        $courses = Course::query()
            ->where('instructor_id', $instructor->id)
            ->withCount('enrollments')
            ->orderBy('id')
            ->get();
        $courseIds = $courses->pluck('id');
        $revenueByCourse = OrderItem::query()
            ->selectRaw('course_id, SUM(price) as revenue')
            ->whereIn('course_id', $courseIds)
            ->whereHas('order', fn ($query) => $query->where('status', 'paid'))
            ->groupBy('course_id')
            ->pluck('revenue', 'course_id');
        $coursesPerformance = $courses->map(fn (Course $course): array => [
            'id' => $course->id,
            'title' => $course->title,
            'status' => $course->status,
            'total_students' => (int) $course->enrollments_count,
            'revenue' => (float) ($revenueByCourse[$course->id] ?? 0),
        ])->all();

        return [
            'total_courses' => $courses->count(),
            'total_students' => $courses->sum('enrollments_count'),
            'total_revenue' => (float) $revenueByCourse->sum(),
            'courses_performance' => $coursesPerformance,
        ];
    }
}
