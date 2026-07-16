<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_unenrolled_user_cannot_review_or_access_lesson_comments(): void
    {
        [$course, $lesson] = $this->createCourseWithLesson();
        $student = $this->createUser('student');
        Sanctum::actingAs($student);

        $this->postJson("/api/courses/{$course->id}/reviews", [
            'rating' => 5,
            'comment' => 'Unauthorized review',
        ])->assertForbidden();

        $this->getJson("/api/lessons/{$lesson->id}/comments")->assertForbidden();
        $this->postJson("/api/lessons/{$lesson->id}/comments", [
            'content' => 'Unauthorized comment',
        ])->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_review_is_updated_instead_of_duplicated_and_course_average_is_recalculated(): void
    {
        [$course] = $this->createCourseWithLesson();
        $firstStudent = $this->createUser('student');
        $secondStudent = $this->createUser('student');
        $this->enroll($firstStudent, $course);
        $this->enroll($secondStudent, $course);

        Sanctum::actingAs($firstStudent);
        $firstReviewId = $this->postJson("/api/courses/{$course->id}/reviews", [
            'rating' => 5,
            'comment' => 'Initial review',
        ])->assertCreated()->json('data.id');

        $this->putJson("/api/courses/{$course->id}/reviews", [
            'rating' => 1,
            'comment' => 'Updated review',
        ])->assertOk()
            ->assertJsonPath('data.id', $firstReviewId)
            ->assertJsonPath('data.rating', 1)
            ->assertJsonPath('data.comment', 'Updated review');

        $this->assertDatabaseCount('reviews', 1);
        $this->assertSame(1.0, (float) $course->fresh()->rating_avg);

        Sanctum::actingAs($secondStudent);
        $this->postJson("/api/courses/{$course->id}/reviews", [
            'rating' => 3,
            'comment' => 'Second review',
        ])->assertCreated();

        $this->assertDatabaseCount('reviews', 2);
        $this->assertSame(2.0, (float) $course->fresh()->rating_avg);

        $this->getJson("/api/courses/{$course->id}/reviews")
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 2)
            ->assertJsonPath('data.items.0.user.name', $secondStudent->name)
            ->assertJsonPath('data.items.0.rating', 3);
    }

    public function test_review_author_or_admin_can_delete_and_rating_average_is_recalculated(): void
    {
        [$course] = $this->createCourseWithLesson();
        $firstStudent = $this->createUser('student');
        $secondStudent = $this->createUser('student');
        $admin = $this->createUser('admin');
        $this->enroll($firstStudent, $course);
        $this->enroll($secondStudent, $course);

        $firstReview = $course->reviews()->create(['user_id' => $firstStudent->id, 'rating' => 5]);
        $secondReview = $course->reviews()->create(['user_id' => $secondStudent->id, 'rating' => 3]);
        $course->update(['rating_avg' => 4]);

        Sanctum::actingAs($secondStudent);
        $this->deleteJson("/api/courses/{$course->id}/reviews/{$firstReview->id}")->assertForbidden();

        Sanctum::actingAs($firstStudent);
        $this->deleteJson("/api/courses/{$course->id}/reviews/{$firstReview->id}")->assertOk();
        $this->assertSame(3.0, (float) $course->fresh()->rating_avg);

        Sanctum::actingAs($admin);
        $this->deleteJson("/api/courses/{$course->id}/reviews/{$secondReview->id}")->assertOk();
        $this->assertSame(0.0, (float) $course->fresh()->rating_avg);
    }

    public function test_comment_reply_is_returned_as_a_tree(): void
    {
        [$course, $lesson] = $this->createCourseWithLesson();
        $firstStudent = $this->createUser('student');
        $secondStudent = $this->createUser('student');
        $this->enroll($firstStudent, $course);
        $this->enroll($secondStudent, $course);

        Sanctum::actingAs($firstStudent);
        $rootId = $this->postJson("/api/lessons/{$lesson->id}/comments", [
            'content' => 'Root comment',
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($secondStudent);
        $replyId = $this->postJson("/api/lessons/{$lesson->id}/comments", [
            'content' => 'Reply comment',
            'parent_id' => $rootId,
        ])->assertCreated()->json('data.id');

        $this->getJson("/api/lessons/{$lesson->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.id', $rootId)
            ->assertJsonPath('data.items.0.content', 'Root comment')
            ->assertJsonPath('data.items.0.replies.0.id', $replyId)
            ->assertJsonPath('data.items.0.replies.0.content', 'Reply comment')
            ->assertJsonPath('data.items.0.replies.0.user.id', $secondStudent->id);
    }

    public function test_deleting_comment_with_replies_keeps_thread_and_child_comments(): void
    {
        [$course, $lesson] = $this->createCourseWithLesson();
        $student = $this->createUser('student');
        $this->enroll($student, $course);
        $root = Comment::query()->create([
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'content' => 'Delete this root',
        ]);
        $reply = Comment::query()->create([
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'parent_id' => $root->id,
            'content' => 'Keep this reply',
        ]);
        Sanctum::actingAs($student);

        $this->deleteJson("/api/comments/{$root->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('comments', ['id' => $root->id]);
        $this->assertDatabaseHas('comments', ['id' => $reply->id, 'content' => 'Keep this reply']);

        $this->getJson("/api/lessons/{$lesson->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $root->id)
            ->assertJsonPath('data.items.0.is_deleted', true)
            ->assertJsonPath('data.items.0.content', 'Bình luận đã bị xóa')
            ->assertJsonPath('data.items.0.replies.0.id', $reply->id)
            ->assertJsonPath('data.items.0.replies.0.content', 'Keep this reply');
    }

    /** @return array{Course, Lesson} */
    private function createCourseWithLesson(): array
    {
        $instructor = $this->createUser('instructor');
        $category = Category::query()->firstOrCreate(
            ['slug' => 'review-comment-tests'],
            ['name' => 'Review Comment Tests'],
        );
        $course = Course::query()->create([
            'instructor_id' => $instructor->id,
            'category_id' => $category->id,
            'title' => 'Reviewable Course '.str()->random(6),
            'slug' => 'reviewable-course-'.str()->random(8),
            'price' => 299000,
            'status' => 'approved',
            'published_at' => now(),
        ]);
        $chapter = Chapter::query()->create([
            'course_id' => $course->id,
            'title' => 'Chapter One',
            'position' => 1,
        ]);
        $lesson = Lesson::query()->create([
            'chapter_id' => $chapter->id,
            'title' => 'Lesson One',
            'position' => 1,
        ]);

        return [$course, $lesson];
    }

    private function createUser(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst($roleName)],
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function enroll(User $user, Course $course): Enrollment
    {
        return Enrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);
    }
}
