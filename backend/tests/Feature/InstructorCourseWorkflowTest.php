<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InstructorCourseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_read_draft_curriculum_and_lesson_but_other_instructor_cannot(): void
    {
        $owner = $this->createUser('instructor', 'owner-workflow@example.test');
        $other = $this->createUser('instructor', 'other-workflow@example.test');
        $course = $this->createCourse($owner, 'draft');
        [$chapter, $lesson] = $this->createCurriculum($course);

        Sanctum::actingAs($owner);
        $this->getJson("/api/courses/{$course->id}/chapters")
            ->assertOk()
            ->assertJsonPath('data.0.id', $chapter->id)
            ->assertJsonPath('data.0.lessons.0.id', $lesson->id)
            ->assertJsonPath('data.0.lessons.0.content', 'Draft lesson content');
        $this->getJson("/api/courses/{$course->id}/lessons/{$lesson->id}")
            ->assertOk()
            ->assertJsonPath('data.content', 'Draft lesson content');

        Sanctum::actingAs($other);
        $this->getJson("/api/courses/{$course->id}/chapters")->assertNotFound();
        $this->getJson("/api/courses/{$course->id}/lessons/{$lesson->id}")->assertNotFound();
    }

    public function test_owner_can_submit_draft_course_when_it_has_chapter_and_lesson(): void
    {
        $owner = $this->createUser('instructor');
        $course = $this->createCourse($owner, 'draft');
        $this->createCurriculum($course);
        Sanctum::actingAs($owner);

        $this->postJson("/api/instructor/courses/{$course->id}/submit")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.reject_reason', null);

        $this->assertSame('pending', $course->fresh()->status);
    }

    public function test_submit_fails_when_course_has_no_lesson(): void
    {
        $owner = $this->createUser('instructor');
        $course = $this->createCourse($owner, 'rejected');
        $course->update(['reject_reason' => 'Add curriculum']);
        Chapter::query()->create(['course_id' => $course->id, 'title' => 'Empty chapter', 'position' => 1]);
        Sanctum::actingAs($owner);

        $this->postJson("/api/instructor/courses/{$course->id}/submit")
            ->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Khóa học cần có ít nhất 1 chương và 1 bài học trước khi gửi duyệt');

        $this->assertSame('rejected', $course->fresh()->status);
    }

    public function test_non_owner_cannot_submit_and_pending_or_approved_cannot_be_resubmitted(): void
    {
        $owner = $this->createUser('instructor', 'submit-owner@example.test');
        $other = $this->createUser('instructor', 'submit-other@example.test');
        $pending = $this->createCourse($owner, 'pending');
        $this->createCurriculum($pending);

        Sanctum::actingAs($other);
        $this->postJson("/api/instructor/courses/{$pending->id}/submit")->assertForbidden();

        Sanctum::actingAs($owner);
        $this->postJson("/api/instructor/courses/{$pending->id}/submit")
            ->assertStatus(400)
            ->assertJsonPath('message', 'Chỉ khóa học draft hoặc rejected mới có thể gửi duyệt.');
    }

    public function test_updating_approved_course_moves_it_back_to_pending(): void
    {
        $owner = $this->createUser('instructor');
        $course = $this->createCourse($owner, 'approved');
        $course->update(['reject_reason' => 'Old reason', 'published_at' => now()]);
        Sanctum::actingAs($owner);

        $this->patchJson("/api/instructor/courses/{$course->id}", ['title' => 'Updated approved course'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated approved course')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.reject_reason', null);

        $course->refresh();
        $this->assertSame('pending', $course->status);
        $this->assertNull($course->published_at);
    }

    private function createUser(string $roleName, ?string $email = null): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName], ['display_name' => ucfirst($roleName)]);

        return User::factory()->create(['role_id' => $role->id, 'email' => $email ?? fake()->unique()->safeEmail()]);
    }

    private function createCourse(User $owner, string $status): Course
    {
        $category = Category::query()->firstOrCreate(['slug' => 'workflow'], ['name' => 'Workflow']);

        return Course::query()->create([
            'instructor_id' => $owner->id,
            'category_id' => $category->id,
            'title' => 'Workflow Course '.fake()->unique()->numberBetween(1, 999999),
            'slug' => 'workflow-course-'.fake()->unique()->numberBetween(1, 999999),
            'price' => 299000,
            'status' => $status,
            'published_at' => $status === 'approved' ? now() : null,
        ]);
    }

    /** @return array{Chapter, Lesson} */
    private function createCurriculum(Course $course): array
    {
        $chapter = Chapter::query()->create(['course_id' => $course->id, 'title' => 'Draft chapter', 'position' => 1]);
        $lesson = Lesson::query()->create([
            'chapter_id' => $chapter->id,
            'title' => 'Draft lesson',
            'content' => 'Draft lesson content',
            'position' => 1,
        ]);

        return [$chapter, $lesson];
    }
}
