<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_create_a_course(): void
    {
        $instructor = $this->userWithRole('instructor');
        $category = $this->createCategory();
        Sanctum::actingAs($instructor);

        $response = $this->postJson('/api/instructor/courses', [
            'category_id' => $category->id,
            'title' => 'Laravel API từ cơ bản đến nâng cao',
            'description' => 'Khóa học xây dựng REST API.',
            'price' => 499000,
            'level' => 'beginner',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.instructor.id', $instructor->id)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.title', 'Laravel API từ cơ bản đến nâng cao');

        $this->assertDatabaseHas('courses', [
            'instructor_id' => $instructor->id,
            'category_id' => $category->id,
            'status' => 'draft',
        ]);
    }

    public function test_course_requirements_are_saved_from_multiline_input(): void
    {
        $instructor = $this->userWithRole('instructor');
        $category = $this->createCategory();
        Sanctum::actingAs($instructor);

        $response = $this->postJson('/api/instructor/courses', [
            'category_id' => $category->id,
            'title' => 'Laravel Requirements',
            'description' => 'Course with requirements.',
            'requirements' => "Biáº¿t PHP cÆ¡ báº£n\nCÃ³ mÃ¡y tÃ­nh cÃ¡ nhÃ¢n\nSáºµn sÃ ng thá»±c hÃ nh",
            'price' => 499000,
            'level' => 'beginner',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.requirements.0', 'Biáº¿t PHP cÆ¡ báº£n')
            ->assertJsonPath('data.requirements.1', 'CÃ³ mÃ¡y tÃ­nh cÃ¡ nhÃ¢n')
            ->assertJsonPath('data.requirements.2', 'Sáºµn sÃ ng thá»±c hÃ nh');
    }

    public function test_student_cannot_create_a_course(): void
    {
        $student = $this->userWithRole('student');
        $category = $this->createCategory();
        Sanctum::actingAs($student);

        $this->postJson('/api/instructor/courses', [
            'category_id' => $category->id,
            'title' => 'Unauthorized course',
            'price' => 100000,
        ])
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('courses', 0);
    }

    public function test_public_listing_only_returns_approved_courses(): void
    {
        $instructor = $this->userWithRole('instructor');
        $category = $this->createCategory();

        $approved = $this->createCourse($instructor, $category, 'Khóa học đã duyệt', 'approved');
        $this->createCourse($instructor, $category, 'Khóa học nháp', 'draft');
        $this->createCourse($instructor, $category, 'Khóa học chờ duyệt', 'pending');
        $this->createCourse($instructor, $category, 'Khóa học bị từ chối', 'rejected');

        $response = $this->getJson('/api/courses');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $approved->id)
            ->assertJsonPath('data.0.status', 'approved');
    }

    public function test_public_listing_searches_approved_courses_by_title_instructor_and_category(): void
    {
        $dockerInstructor = $this->userWithRole('instructor');
        $dockerInstructor->update(['name' => 'Docker Mentor']);
        $otherInstructor = $this->userWithRole('instructor');
        $category = $this->createCategory();
        $cloudCategory = Category::query()->create(['name' => 'DevOps & Cloud', 'slug' => 'devops-cloud']);

        $titleMatch = $this->createCourse($otherInstructor, $category, 'Docker for Teams', 'approved');
        $instructorMatch = $this->createCourse($dockerInstructor, $category, 'Container Basics', 'approved');
        $categoryMatch = $this->createCourse($otherInstructor, $cloudCategory, 'Kubernetes Operations', 'approved');
        $this->createCourse($dockerInstructor, $category, 'Docker draft only', 'draft');
        $this->createCourse($otherInstructor, $category, 'Laravel Fundamentals', 'approved');

        $response = $this->getJson('/api/courses?search=Docker');

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($titleMatch->id));
        $this->assertTrue($ids->contains($instructorMatch->id));
        $this->assertFalse($ids->contains($categoryMatch->id));

        $categoryResponse = $this->getJson('/api/courses?search=Cloud');
        $categoryIds = collect($categoryResponse->json('data'))->pluck('id');
        $this->assertTrue($categoryIds->contains($categoryMatch->id));
    }

    public function test_course_detail_reports_enrollment_for_optional_authenticated_user(): void
    {
        $instructor = $this->userWithRole('instructor');
        $student = $this->userWithRole('student');
        $category = $this->createCategory();
        $course = $this->createCourse($instructor, $category, 'Owned course', 'approved');

        $this->getJson("/api/courses/{$course->id}")
            ->assertOk()
            ->assertJsonPath('data.is_enrolled', false);

        Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);
        Sanctum::actingAs($student);

        $this->getJson("/api/courses/{$course->id}")
            ->assertOk()
            ->assertJsonPath('data.is_enrolled', true);
    }

    public function test_course_detail_includes_instructor_bio_and_real_statistics(): void
    {
        $instructor = $this->userWithRole('instructor');
        $instructor->update(['bio' => 'Backend mentor with production experience.']);
        $studentOne = $this->userWithRole('student');
        $studentTwo = $this->userWithRole('student');
        $category = $this->createCategory();

        $course = $this->createCourse($instructor, $category, 'Instructor stats one', 'approved');
        $course->update(['rating_avg' => 4.0]);
        $secondCourse = $this->createCourse($instructor, $category, 'Instructor stats two', 'approved');
        $secondCourse->update(['rating_avg' => 5.0]);

        Enrollment::query()->create(['user_id' => $studentOne->id, 'course_id' => $course->id, 'enrolled_at' => now()]);
        Enrollment::query()->create(['user_id' => $studentOne->id, 'course_id' => $secondCourse->id, 'enrolled_at' => now()]);
        Enrollment::query()->create(['user_id' => $studentTwo->id, 'course_id' => $course->id, 'enrolled_at' => now()]);

        $this->getJson("/api/courses/{$course->id}")
            ->assertOk()
            ->assertJsonPath('data.instructor.bio', 'Backend mentor with production experience.')
            ->assertJsonPath('data.instructor.instructor_stats.total_courses', 2)
            ->assertJsonPath('data.instructor.instructor_stats.total_students', 2)
            ->assertJsonPath('data.instructor.instructor_stats.rating_avg', 4.5);
    }

    public function test_related_courses_return_approved_courses_from_same_category(): void
    {
        $instructor = $this->userWithRole('instructor');
        $category = $this->createCategory();
        $otherCategory = Category::query()->create(['name' => 'Backend', 'slug' => 'backend']);
        $course = $this->createCourse($instructor, $category, 'Current course', 'approved');
        $bestRelated = $this->createCourse($instructor, $category, 'Best related', 'approved');
        $bestRelated->update(['rating_avg' => 4.9]);
        $lowRelated = $this->createCourse($instructor, $category, 'Low related', 'approved');
        $lowRelated->update(['rating_avg' => 4.1]);
        $this->createCourse($instructor, $category, 'Draft related', 'draft');
        $this->createCourse($instructor, $otherCategory, 'Other category', 'approved');

        $response = $this->getJson("/api/courses/{$course->id}/related");

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $bestRelated->id)
            ->assertJsonPath('data.1.id', $lowRelated->id);
    }

    public function test_public_instructor_courses_exclude_current_course(): void
    {
        $instructor = $this->userWithRole('instructor');
        $otherInstructor = $this->userWithRole('instructor');
        $category = $this->createCategory();
        $current = $this->createCourse($instructor, $category, 'Current instructor course', 'approved');
        $other = $this->createCourse($instructor, $category, 'Another instructor course', 'approved');
        $this->createCourse($instructor, $category, 'Instructor draft', 'draft');
        $this->createCourse($otherInstructor, $category, 'Other instructor course', 'approved');

        $response = $this->getJson("/api/instructors/{$instructor->id}/courses?exclude_course_id={$current->id}");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $other->id)
            ->assertJsonPath('data.pagination.total', 1);
    }

    public function test_owner_and_admin_can_preview_unpublished_course_but_other_users_cannot(): void
    {
        $owner = $this->userWithRole('instructor');
        $otherInstructor = $this->userWithRole('instructor');
        $admin = $this->userWithRole('admin');
        $category = $this->createCategory();
        $course = $this->createCourse($owner, $category, 'Draft preview', 'draft');

        Sanctum::actingAs($owner);
        $this->getJson("/api/courses/{$course->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $course->id);

        Sanctum::actingAs($admin);
        $this->getJson("/api/courses/{$course->id}")->assertOk();

        Sanctum::actingAs($otherInstructor);
        $this->getJson("/api/courses/{$course->id}")->assertNotFound();

        $this->app['auth']->forgetGuards();
        $this->getJson("/api/courses/{$course->id}")->assertNotFound();
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst($roleName)]
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function createCategory(): Category
    {
        return Category::query()->create([
            'name' => 'Lập trình Web',
            'slug' => 'lap-trinh-web',
        ]);
    }

    private function createCourse(
        User $instructor,
        Category $category,
        string $title,
        string $status
    ): Course {
        return Course::query()->create([
            'instructor_id' => $instructor->id,
            'category_id' => $category->id,
            'title' => $title,
            'slug' => str()->slug($title),
            'price' => 100000,
            'status' => $status,
            'published_at' => $status === 'approved' ? now() : null,
        ]);
    }
}
