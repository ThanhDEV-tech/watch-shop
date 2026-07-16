<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InstructorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_dashboard_returns_exact_own_course_statistics(): void
    {
        $instructor = $this->createUser('instructor', 'Owner Instructor');
        $otherInstructor = $this->createUser('instructor', 'Other Instructor');
        $firstStudent = $this->createUser('student', 'First Student');
        $secondStudent = $this->createUser('student', 'Second Student');
        $thirdStudent = $this->createUser('student', 'Third Student');
        $firstCourse = $this->createCourse($instructor, 'Owner Course One');
        $secondCourse = $this->createCourse($instructor, 'Owner Course Two');
        $otherCourse = $this->createCourse($otherInstructor, 'Foreign Course');

        $this->enroll($firstStudent, $firstCourse, 20);
        $this->enroll($secondStudent, $firstCourse, 50);
        $this->enroll($firstStudent, $secondCourse, 10);
        $this->enroll($thirdStudent, $otherCourse, 90);

        $paidOrder = $this->createOrder($firstStudent, 'PERFORMANCE-PAID', 'paid');
        $this->createOrderItem($paidOrder, $firstCourse, 100000);
        $this->createOrderItem($paidOrder, $secondCourse, 200000);
        $this->createOrderItem($paidOrder, $otherCourse, 500000);
        $secondPaidOrder = $this->createOrder($secondStudent, 'PERFORMANCE-PAID-2', 'paid');
        $this->createOrderItem($secondPaidOrder, $firstCourse, 50000);
        $pendingOrder = $this->createOrder($firstStudent, 'PERFORMANCE-PENDING', 'pending');
        $this->createOrderItem($pendingOrder, $firstCourse, 999000);
        Sanctum::actingAs($instructor);

        $response = $this->getJson('/api/instructor/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_courses', 2)
            ->assertJsonPath('data.total_students', 3)
            ->assertJsonPath('data.total_revenue', 350000)
            ->assertJsonCount(2, 'data.courses_performance');

        $performance = collect($response->json('data.courses_performance'))->keyBy('id');
        $this->assertSame(2, $performance[$firstCourse->id]['total_students']);
        $this->assertSame(150000, (int) $performance[$firstCourse->id]['revenue']);
        $this->assertSame(1, $performance[$secondCourse->id]['total_students']);
        $this->assertSame(200000, (int) $performance[$secondCourse->id]['revenue']);

        Sanctum::actingAs($otherInstructor);
        $this->getJson('/api/instructor/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.total_courses', 1)
            ->assertJsonPath('data.total_students', 1)
            ->assertJsonPath('data.total_revenue', 500000)
            ->assertJsonCount(1, 'data.courses_performance');
    }

    public function test_instructor_can_list_students_and_progress_for_own_course(): void
    {
        $instructor = $this->createUser('instructor');
        $student = $this->createUser('student', 'Progress Student');
        $course = $this->createCourse($instructor, 'Progress Course');
        $enrollment = $this->enroll($student, $course, 50);
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
        LessonProgress::query()->create([
            'enrollment_id' => $enrollment->id,
            'lesson_id' => $lesson->id,
            'is_completed' => true,
            'completed_at' => now(),
        ]);
        Sanctum::actingAs($instructor);

        $this->getJson("/api/instructor/courses/{$course->id}/students")
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.student.id', $student->id)
            ->assertJsonPath('data.items.0.progress_percent', 50)
            ->assertJsonPath('data.items.0.completed_lessons', 1)
            ->assertJsonPath('data.items.0.enrollment_id', $enrollment->id);
    }

    public function test_instructor_cannot_view_students_of_another_instructors_course(): void
    {
        $instructor = $this->createUser('instructor');
        $otherInstructor = $this->createUser('instructor');
        $foreignCourse = $this->createCourse($otherInstructor, 'Foreign Students Course');
        Sanctum::actingAs($instructor);

        $this->getJson("/api/instructor/courses/{$foreignCourse->id}/students")
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_non_instructor_is_forbidden_from_every_instructor_dashboard_endpoint(): void
    {
        $student = $this->createUser('student');
        $instructor = $this->createUser('instructor');
        $course = $this->createCourse($instructor, 'Protected Course');
        Sanctum::actingAs($student);

        $this->getJson('/api/instructor/dashboard/stats')->assertForbidden();
        $this->getJson("/api/instructor/courses/{$course->id}/students")->assertForbidden();
    }

    private function createUser(string $roleName, string $name = 'Test User'): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst($roleName)],
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    private function createCourse(User $instructor, string $title): Course
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'instructor-dashboard'],
            ['name' => 'Instructor Dashboard'],
        );

        return Course::query()->create([
            'instructor_id' => $instructor->id,
            'category_id' => $category->id,
            'title' => $title,
            'slug' => str($title)->slug().'-'.fake()->unique()->numberBetween(1, 999999),
            'price' => 500000,
            'status' => 'approved',
        ]);
    }

    private function enroll(User $student, Course $course, int $progress): Enrollment
    {
        return Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'progress_percent' => $progress,
            'enrolled_at' => now(),
        ]);
    }

    private function createOrder(User $student, string $code, string $status): Order
    {
        return Order::query()->create([
            'user_id' => $student->id,
            'code' => $code,
            'total_amount' => 999999,
            'status' => $status,
            'paid_at' => $status === 'paid' ? now() : null,
        ]);
    }

    private function createOrderItem(Order $order, Course $course, int $price): OrderItem
    {
        return OrderItem::query()->create([
            'order_id' => $order->id,
            'course_id' => $course->id,
            'price' => $price,
        ]);
    }
}
