<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LearningTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrolled_student_can_list_owned_courses_and_view_lesson_content(): void
    {
        $student = $this->createUser('student');
        [$course, $lessons] = $this->createCourseWithLessons(1);
        Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'progress_percent' => 25,
            'enrolled_at' => now(),
        ]);
        Sanctum::actingAs($student);

        $this->getJson('/api/my-courses')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.course.id', $course->id)
            ->assertJsonPath('data.0.progress_percent', 25);

        $this->getJson("/api/courses/{$course->id}/lessons/{$lessons[0]->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $lessons[0]->id)
            ->assertJsonPath('data.content', $lessons[0]->content);
    }

    public function test_unenrolled_student_cannot_view_locked_lesson_but_can_view_free_preview(): void
    {
        $student = $this->createUser('student');
        [$course, $lessons] = $this->createCourseWithLessons(2);
        $lessons[1]->update(['is_free_preview' => true]);
        Sanctum::actingAs($student);

        $this->getJson("/api/courses/{$course->id}/lessons/{$lessons[0]->id}")
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->getJson("/api/courses/{$course->id}/lessons/{$lessons[1]->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', $lessons[1]->content);
    }

    public function test_curriculum_hides_locked_lesson_content_but_exposes_free_preview_and_enrolled_content(): void
    {
        $student = $this->createUser('student');
        [$course, $lessons] = $this->createCourseWithLessons(2);
        $lessons[1]->update(['is_free_preview' => true]);
        Sanctum::actingAs($student);

        $this->getJson("/api/courses/{$course->id}/chapters")
            ->assertOk()
            ->assertJsonPath('data.0.lessons.0.content', null)
            ->assertJsonPath('data.0.lessons.0.youtube_url', null)
            ->assertJsonPath('data.0.lessons.1.content', $lessons[1]->content)
            ->assertJsonPath('data.0.lessons.1.youtube_url', $lessons[1]->youtube_url);

        Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        $this->getJson("/api/courses/{$course->id}/chapters")
            ->assertOk()
            ->assertJsonPath('data.0.lessons.0.content', $lessons[0]->content)
            ->assertJsonPath('data.0.lessons.0.youtube_url', $lessons[0]->youtube_url)
            ->assertJsonPath('data.0.lessons.1.content', $lessons[1]->content)
            ->assertJsonPath('data.0.lessons.1.youtube_url', $lessons[1]->youtube_url);
    }

    public function test_completing_lesson_updates_enrollment_progress_percent(): void
    {
        $student = $this->createUser('student');
        [$course, $lessons] = $this->createCourseWithLessons(4);
        $enrollment = Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);
        Sanctum::actingAs($student);

        $this->postJson("/api/lessons/{$lessons[0]->id}/complete")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.progress_percent', 25)
            ->assertJsonPath('data.completed_at', null);

        $this->assertDatabaseHas('lesson_progress', [
            'enrollment_id' => $enrollment->id,
            'lesson_id' => $lessons[0]->id,
            'is_completed' => true,
        ]);
        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'progress_percent' => 25,
            'completed_at' => null,
        ]);
        $this->getJson("/api/courses/{$course->id}/chapters")
            ->assertOk()
            ->assertJsonPath('data.0.lessons.0.is_completed', true)
            ->assertJsonPath('data.0.lessons.1.is_completed', false);
    }

    public function test_completing_every_lesson_sets_course_enrollment_completed_at(): void
    {
        $student = $this->createUser('student');
        [$course, $lessons] = $this->createCourseWithLessons(2);
        $enrollment = Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);
        Sanctum::actingAs($student);

        foreach ($lessons as $lesson) {
            $this->postJson("/api/lessons/{$lesson->id}/complete")->assertOk();
        }

        $enrollment->refresh();

        $this->assertSame(100, $enrollment->progress_percent);
        $this->assertNotNull($enrollment->completed_at);
        $this->assertDatabaseCount('lesson_progress', 2);
    }

    /** @return array{Course, array<int, Lesson>} */
    private function createCourseWithLessons(int $lessonCount): array
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'learning-tests'],
            ['name' => 'Learning Tests'],
        );
        $course = Course::query()->create([
            'instructor_id' => $this->createUser('instructor')->id,
            'category_id' => $category->id,
            'title' => 'Learning Course '.str()->random(8),
            'slug' => 'learning-course-'.str()->random(8),
            'price' => 299000,
            'status' => 'approved',
            'published_at' => now(),
        ]);
        $chapter = Chapter::query()->create([
            'course_id' => $course->id,
            'title' => 'Chapter 1',
            'position' => 1,
        ]);
        $lessons = [];

        for ($position = 1; $position <= $lessonCount; $position++) {
            $lessons[] = Lesson::query()->create([
                'chapter_id' => $chapter->id,
                'title' => "Lesson {$position}",
                'content' => "Full lesson content {$position}",
                'youtube_url' => 'https://www.youtube.com/watch?v=test'.$position,
                'duration_seconds' => 600,
                'position' => $position,
                'is_free_preview' => false,
            ]);
        }

        return [$course, $lessons];
    }

    private function createUser(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst($roleName)],
        );

        return User::factory()->create(['role_id' => $role->id]);
    }
}
