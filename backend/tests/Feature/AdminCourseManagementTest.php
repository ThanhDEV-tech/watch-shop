<?php

namespace Tests\Feature;

use App\Mail\CourseReviewedMail;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCourseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_lists_all_courses_and_can_filter_and_search(): void
    {
        $admin = $this->createUser('admin', 'Main Admin');
        $firstInstructor = $this->createUser('instructor', 'Alice Teacher');
        $secondInstructor = $this->createUser('instructor', 'Bob Teacher');
        $category = $this->createCategory();
        $pending = $this->createCourse($firstInstructor, $category, 'Laravel Approval', 'pending');
        $this->createCourse($secondInstructor, $category, 'Vue Approved', 'approved');
        $this->createCourse($secondInstructor, $category, 'Draft Course', 'draft');
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/courses')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 3)
            ->assertJsonCount(3, 'data.items');

        $this->getJson('/api/admin/courses?status=pending&search=Alice')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.id', $pending->id)
            ->assertJsonPath('data.items.0.instructor.name', 'Alice Teacher');
    }

    public function test_admin_can_view_full_course_detail_with_curriculum(): void
    {
        $admin = $this->createUser('admin');
        $instructor = $this->createUser('instructor');
        $category = $this->createCategory();
        $course = $this->createCourse($instructor, $category, 'Detailed Course', 'pending');
        $course->update(['description' => 'Course description', 'content' => 'Full course content']);
        $chapter = Chapter::query()->create([
            'course_id' => $course->id,
            'title' => 'Chapter One',
            'description' => 'Chapter description',
            'position' => 1,
        ]);
        $lesson = Lesson::query()->create([
            'chapter_id' => $chapter->id,
            'title' => 'Lesson One',
            'content' => 'Lesson content',
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'position' => 1,
        ]);
        Sanctum::actingAs($admin);

        $this->getJson("/api/admin/courses/{$course->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $course->id)
            ->assertJsonPath('data.description', 'Course description')
            ->assertJsonPath('data.content', 'Full course content')
            ->assertJsonPath('data.instructor.id', $instructor->id)
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.chapters.0.id', $chapter->id)
            ->assertJsonPath('data.chapters.0.lessons.0.id', $lesson->id)
            ->assertJsonPath('data.chapters.0.lessons.0.content', 'Lesson content');
    }

    public function test_admin_can_approve_a_pending_course(): void
    {
        Mail::fake();
        $admin = $this->createUser('admin');
        $course = $this->createCourse($this->createUser('instructor'), $this->createCategory(), 'Approve Me', 'pending');
        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/courses/{$course->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.reject_reason', null);

        $this->assertSame('approved', $course->fresh()->status);
        $this->assertNotNull($course->fresh()->published_at);
        Mail::assertQueued(CourseReviewedMail::class, function (CourseReviewedMail $mail) use ($course): bool {
            return $mail->course->is($course)
                && $mail->course->status === 'approved'
                && $mail->hasTo($course->instructor->email);
        });
    }

    public function test_reject_requires_reason_and_instructor_can_see_reject_reason(): void
    {
        Mail::fake();
        $admin = $this->createUser('admin');
        $instructor = $this->createUser('instructor');
        $course = $this->createCourse($instructor, $this->createCategory(), 'Reject Me', 'pending');
        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/courses/{$course->id}/reject", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason', 'data.errors');

        $this->patchJson("/api/admin/courses/{$course->id}/reject", ['reason' => 'Nội dung khóa học chưa đầy đủ.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.reject_reason', 'Nội dung khóa học chưa đầy đủ.');

        Sanctum::actingAs($instructor);
        $this->getJson('/api/instructor/courses')
            ->assertOk()
            ->assertJsonPath('data.0.id', $course->id)
            ->assertJsonPath('data.0.reject_reason', 'Nội dung khóa học chưa đầy đủ.');

        Mail::assertQueued(CourseReviewedMail::class, function (CourseReviewedMail $mail) use ($course): bool {
            return $mail->course->is($course)
                && $mail->course->status === 'rejected'
                && $mail->course->reject_reason === 'Nội dung khóa học chưa đầy đủ.'
                && $mail->hasTo($course->instructor->email);
        });
    }

    public function test_non_admin_cannot_access_admin_course_management(): void
    {
        $instructor = $this->createUser('instructor');
        $course = $this->createCourse($instructor, $this->createCategory(), 'Protected Course', 'pending');
        Sanctum::actingAs($instructor);

        foreach ([
            ['GET', '/api/admin/courses', []],
            ['GET', "/api/admin/courses/{$course->id}", []],
            ['PATCH', "/api/admin/courses/{$course->id}/approve", []],
            ['PATCH', "/api/admin/courses/{$course->id}/reject", ['reason' => 'No access']],
        ] as [$method, $uri, $data]) {
            $this->json($method, $uri, $data)->assertForbidden();
        }
    }

    private function createUser(string $roleName, string $name = 'Test User'): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName], ['display_name' => ucfirst($roleName)]);

        return User::factory()->create(['role_id' => $role->id, 'name' => $name]);
    }

    private function createCategory(): Category
    {
        return Category::query()->firstOrCreate(['slug' => 'admin-course-category'], ['name' => 'Admin Course Category']);
    }

    private function createCourse(User $instructor, Category $category, string $title, string $status): Course
    {
        return Course::query()->create([
            'instructor_id' => $instructor->id,
            'category_id' => $category->id,
            'title' => $title,
            'slug' => str($title)->slug().'-'.fake()->unique()->numberBetween(1, 999999),
            'price' => 499000,
            'status' => $status,
            'published_at' => $status === 'approved' ? now() : null,
        ]);
    }
}
