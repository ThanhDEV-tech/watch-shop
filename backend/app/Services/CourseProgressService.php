<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CourseProgressService
{
    /** @throws AuthorizationException */
    public function completeLesson(User $user, Lesson $lesson): Enrollment
    {
        return DB::transaction(function () use ($user, $lesson): Enrollment {
            $courseId = $lesson->chapter()->value('course_id');
            $enrollment = Enrollment::query()
                ->where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->lockForUpdate()
                ->first();

            if (! $enrollment) {
                throw new AuthorizationException('Bạn chưa đăng ký khóa học này.');
            }

            LessonProgress::query()->updateOrCreate(
                [
                    'enrollment_id' => $enrollment->id,
                    'lesson_id' => $lesson->id,
                ],
                [
                    'is_completed' => true,
                    'completed_at' => now(),
                ],
            );

            $totalLessons = Lesson::query()
                ->whereHas('chapter', fn ($query) => $query->where('course_id', $courseId))
                ->count();
            $completedLessons = LessonProgress::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('is_completed', true)
                ->whereHas('lesson.chapter', fn ($query) => $query->where('course_id', $courseId))
                ->count();
            $progressPercent = $totalLessons > 0
                ? (int) round(($completedLessons / $totalLessons) * 100)
                : 0;

            $enrollment->update([
                'progress_percent' => $progressPercent,
                'completed_at' => $progressPercent === 100
                    ? ($enrollment->completed_at ?? now())
                    : null,
            ]);

            return $enrollment->refresh();
        });
    }
}
