<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    /** @param array{rating: int, comment?: string|null} $data */
    public function upsert(User $user, Course $course, array $data): Review
    {
        return DB::transaction(function () use ($user, $course, $data): Review {
            $review = Review::query()->updateOrCreate(
                ['user_id' => $user->id, 'course_id' => $course->id],
                $data,
            );

            $this->refreshCourseRating($course);

            return $review->load('user');
        });
    }

    public function delete(Review $review): void
    {
        DB::transaction(function () use ($review): void {
            $course = $review->course;
            $review->delete();
            $this->refreshCourseRating($course);
        });
    }

    private function refreshCourseRating(Course $course): void
    {
        $ratingAverage = (float) ($course->reviews()->avg('rating') ?? 0);
        $course->update(['rating_avg' => round($ratingAverage, 2)]);
    }
}
