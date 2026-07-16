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

class CurriculumTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_manage_full_curriculum_for_draft_pending_and_rejected_courses(): void
    {
        $owner = $this->createInstructor('all-status-owner@example.test');
        Sanctum::actingAs($owner);

        foreach (['draft', 'pending', 'rejected'] as $status) {
            $course = $this->createCourse($owner, $status);
            $chapterResponse = $this->postJson("/api/instructor/courses/{$course->id}/chapters", [
                'title' => "{$status} chapter",
                'position' => 1,
            ])->assertCreated();
            $chapterId = $chapterResponse->json('data.id');

            $lessonResponse = $this->postJson("/api/instructor/chapters/{$chapterId}/lessons", [
                'title' => "{$status} lesson",
                'position' => 1,
            ])->assertCreated();
            $lessonId = $lessonResponse->json('data.id');

            $this->putJson("/api/instructor/chapters/{$chapterId}", [
                'title' => "{$status} chapter updated",
            ])->assertOk();
            $this->putJson("/api/instructor/lessons/{$lessonId}", [
                'title' => "{$status} lesson updated",
            ])->assertOk();

            $this->deleteJson("/api/instructor/lessons/{$lessonId}")->assertOk();
            $this->deleteJson("/api/instructor/chapters/{$chapterId}")->assertOk();
            $this->assertDatabaseMissing('chapters', ['id' => $chapterId]);
            $this->assertDatabaseMissing('lessons', ['id' => $lessonId]);
        }
    }

    public function test_instructor_can_create_chapter_and_lesson_for_own_course(): void
    {
        $instructor = $this->createInstructor();
        $course = $this->createCourse($instructor);
        Sanctum::actingAs($instructor);

        $chapterResponse = $this->postJson("/api/instructor/courses/{$course->id}/chapters", [
            'title' => 'Bắt đầu với Laravel',
            'description' => 'Kiến thức nền tảng.',
            'position' => 1,
        ]);

        $chapterResponse
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.course_id', $course->id)
            ->assertJsonPath('data.position', 1);

        $chapterId = $chapterResponse->json('data.id');
        $lessonResponse = $this->postJson("/api/instructor/chapters/{$chapterId}/lessons", [
            'title' => 'Cài đặt môi trường',
            'content' => 'Nội dung văn bản dùng làm context AI.',
            'youtube_url' => 'https://www.youtube.com/watch?v=example',
            'duration_seconds' => 600,
            'position' => 1,
            'is_free_preview' => true,
        ]);

        $lessonResponse
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.chapter_id', $chapterId)
            ->assertJsonPath('data.is_free_preview', true);

        $this->assertDatabaseHas('lessons', [
            'chapter_id' => $chapterId,
            'title' => 'Cài đặt môi trường',
        ]);
    }

    public function test_instructor_cannot_modify_another_instructors_curriculum(): void
    {
        $owner = $this->createInstructor('owner@example.com');
        $otherInstructor = $this->createInstructor('other@example.com');
        $course = $this->createCourse($owner);
        $chapter = Chapter::query()->create([
            'course_id' => $course->id,
            'title' => 'Owner chapter',
            'position' => 1,
        ]);
        $lesson = Lesson::query()->create([
            'chapter_id' => $chapter->id,
            'title' => 'Owner lesson',
            'position' => 1,
        ]);
        Sanctum::actingAs($otherInstructor);

        $this->postJson("/api/instructor/courses/{$course->id}/chapters", [
            'title' => 'Unauthorized chapter',
        ])->assertForbidden()->assertJsonPath('success', false);

        $this->putJson("/api/instructor/chapters/{$chapter->id}", [
            'title' => 'Unauthorized chapter update',
        ])->assertForbidden();

        $this->deleteJson("/api/instructor/chapters/{$chapter->id}")
            ->assertForbidden();

        $this->postJson("/api/instructor/chapters/{$chapter->id}/lessons", [
            'title' => 'Unauthorized lesson',
        ])->assertForbidden();

        $this->patchJson("/api/instructor/lessons/{$lesson->id}", [
            'title' => 'Unauthorized lesson update',
        ])->assertForbidden()->assertJsonPath('success', false);

        $this->deleteJson("/api/instructor/lessons/{$lesson->id}")
            ->assertForbidden();

        $this->assertDatabaseMissing('chapters', ['title' => 'Unauthorized chapter']);
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'title' => 'Owner lesson',
        ]);
    }

    public function test_admin_can_manage_all_curriculum_operations(): void
    {
        $owner = $this->createInstructor('admin-managed-owner@example.test');
        $admin = $this->createUserWithRole('admin', 'curriculum-admin@example.test');
        $course = $this->createCourse($owner, 'rejected');
        Sanctum::actingAs($admin);

        $chapterResponse = $this->postJson("/api/instructor/courses/{$course->id}/chapters", [
            'title' => 'Admin chapter',
            'position' => 1,
        ])->assertCreated();
        $chapterId = $chapterResponse->json('data.id');

        $lessonResponse = $this->postJson("/api/instructor/chapters/{$chapterId}/lessons", [
            'title' => 'Admin lesson',
            'position' => 1,
        ])->assertCreated();
        $lessonId = $lessonResponse->json('data.id');

        $this->putJson("/api/instructor/chapters/{$chapterId}", ['title' => 'Admin chapter updated'])
            ->assertOk();
        $this->putJson("/api/instructor/lessons/{$lessonId}", ['title' => 'Admin lesson updated'])
            ->assertOk();
        $this->deleteJson("/api/instructor/lessons/{$lessonId}")->assertOk();
        $this->deleteJson("/api/instructor/chapters/{$chapterId}")->assertOk();
    }

    public function test_owner_can_reorder_all_chapters_and_positions_are_persisted(): void
    {
        $owner = $this->createInstructor('reorder-chapters@example.test');
        $course = $this->createCourse($owner);
        $first = Chapter::query()->create(['course_id' => $course->id, 'title' => 'First', 'position' => 1]);
        $second = Chapter::query()->create(['course_id' => $course->id, 'title' => 'Second', 'position' => 2]);
        Sanctum::actingAs($owner);

        $this->patchJson("/api/instructor/courses/{$course->id}/chapters/reorder", [
            'items' => [
                ['id' => $second->id, 'position' => 1],
                ['id' => $first->id, 'position' => 2],
            ],
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $second->id)
            ->assertJsonPath('data.1.id', $first->id);

        $this->assertDatabaseHas('chapters', ['id' => $second->id, 'position' => 1]);
        $this->assertDatabaseHas('chapters', ['id' => $first->id, 'position' => 2]);
    }

    public function test_owner_can_reorder_lessons_only_inside_the_same_chapter(): void
    {
        $owner = $this->createInstructor('reorder-lessons@example.test');
        $course = $this->createCourse($owner);
        $chapter = Chapter::query()->create(['course_id' => $course->id, 'title' => 'Chapter', 'position' => 1]);
        $otherChapter = Chapter::query()->create(['course_id' => $course->id, 'title' => 'Other chapter', 'position' => 2]);
        $first = Lesson::query()->create(['chapter_id' => $chapter->id, 'title' => 'First', 'position' => 1]);
        $second = Lesson::query()->create(['chapter_id' => $chapter->id, 'title' => 'Second', 'position' => 2]);
        $foreignLesson = Lesson::query()->create(['chapter_id' => $otherChapter->id, 'title' => 'Foreign', 'position' => 1]);
        Sanctum::actingAs($owner);

        $this->patchJson("/api/instructor/chapters/{$chapter->id}/lessons/reorder", [
            'items' => [
                ['id' => $second->id, 'position' => 1],
                ['id' => $first->id, 'position' => 2],
            ],
        ])->assertOk()
            ->assertJsonPath('data.0.id', $second->id)
            ->assertJsonPath('data.1.id', $first->id);

        $this->patchJson("/api/instructor/chapters/{$chapter->id}/lessons/reorder", [
            'items' => [
                ['id' => $foreignLesson->id, 'position' => 1],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.id', 'data.errors');

        $this->assertDatabaseHas('lessons', ['id' => $second->id, 'position' => 1]);
        $this->assertDatabaseHas('lessons', ['id' => $first->id, 'position' => 2]);
        $this->assertDatabaseHas('lessons', ['id' => $foreignLesson->id, 'position' => 1]);
    }

    public function test_non_owner_cannot_reorder_curriculum(): void
    {
        $owner = $this->createInstructor('reorder-owner@example.test');
        $other = $this->createInstructor('reorder-other@example.test');
        $course = $this->createCourse($owner);
        $chapter = Chapter::query()->create(['course_id' => $course->id, 'title' => 'Owner chapter', 'position' => 1]);
        $lesson = Lesson::query()->create(['chapter_id' => $chapter->id, 'title' => 'Owner lesson', 'position' => 1]);
        Sanctum::actingAs($other);

        $this->patchJson("/api/instructor/courses/{$course->id}/chapters/reorder", [
            'items' => [['id' => $chapter->id, 'position' => 1]],
        ])->assertForbidden();

        $this->patchJson("/api/instructor/chapters/{$chapter->id}/lessons/reorder", [
            'items' => [['id' => $lesson->id, 'position' => 1]],
        ])->assertForbidden();
    }

    public function test_new_chapters_and_lessons_are_appended_automatically(): void
    {
        $owner = $this->createInstructor('auto-position@example.test');
        $course = $this->createCourse($owner);
        Chapter::query()->create(['course_id' => $course->id, 'title' => 'Existing', 'position' => 4]);
        Sanctum::actingAs($owner);

        $chapterResponse = $this->postJson("/api/instructor/courses/{$course->id}/chapters", [
            'title' => 'Appended chapter',
        ])->assertCreated()->assertJsonPath('data.position', 5);

        $chapterId = $chapterResponse->json('data.id');
        Lesson::query()->create(['chapter_id' => $chapterId, 'title' => 'Existing lesson', 'position' => 3]);

        $this->postJson("/api/instructor/chapters/{$chapterId}/lessons", [
            'title' => 'Appended lesson',
        ])->assertCreated()->assertJsonPath('data.position', 4);
    }

    public function test_public_can_view_ordered_chapters_and_lessons_of_approved_course(): void
    {
        $course = $this->createCourse($this->createInstructor(), 'approved');
        $secondChapter = Chapter::query()->create([
            'course_id' => $course->id,
            'title' => 'Chương thứ hai',
            'position' => 2,
        ]);
        $firstChapter = Chapter::query()->create([
            'course_id' => $course->id,
            'title' => 'Chương thứ nhất',
            'position' => 1,
        ]);
        Lesson::query()->create([
            'chapter_id' => $firstChapter->id,
            'title' => 'Bài thứ hai',
            'content' => 'AI context thứ hai.',
            'position' => 2,
        ]);
        Lesson::query()->create([
            'chapter_id' => $firstChapter->id,
            'title' => 'Bài thứ nhất',
            'content' => 'AI context thứ nhất.',
            'position' => 1,
            'is_free_preview' => true,
        ]);

        $response = $this->getJson("/api/courses/{$course->id}/chapters");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $firstChapter->id)
            ->assertJsonPath('data.0.lessons.0.title', 'Bài thứ nhất')
            ->assertJsonPath('data.0.lessons.0.content', 'AI context thứ nhất.')
            ->assertJsonPath('data.0.lessons.1.content', null)
            ->assertJsonPath('data.1.id', $secondChapter->id);
    }

    private function createInstructor(?string $email = null): User
    {
        return $this->createUserWithRole('instructor', $email);
    }

    private function createUserWithRole(string $roleName, ?string $email = null): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst($roleName)]
        );

        return User::factory()->create([
            'role_id' => $role->id,
            ...($email ? ['email' => $email] : []),
        ]);
    }

    private function createCourse(User $instructor, string $status = 'draft'): Course
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'lap-trinh-web'],
            ['name' => 'Lập trình Web']
        );

        return Course::query()->create([
            'instructor_id' => $instructor->id,
            'category_id' => $category->id,
            'title' => 'Laravel Curriculum '.str()->random(8),
            'slug' => 'laravel-curriculum-'.str()->random(8),
            'price' => 100000,
            'status' => $status,
            'published_at' => $status === 'approved' ? now() : null,
        ]);
    }
}
