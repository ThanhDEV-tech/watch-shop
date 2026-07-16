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

class CommerceTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_add_an_approved_course_to_cart(): void
    {
        $student = $this->createUser('student');
        $course = $this->createCourse(250000);
        Sanctum::actingAs($student);

        $response = $this->postJson('/api/cart/items', [
            'course_id' => $course->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.course_id', $course->id)
            ->assertJsonPath('data.items.0.price_snapshot', '250000.00');

        $this->assertDatabaseHas('cart_items', [
            'course_id' => $course->id,
            'price_snapshot' => 250000,
        ]);
    }

    public function test_student_cannot_add_an_owned_course_to_cart(): void
    {
        $student = $this->createUser('student');
        $course = $this->createCourse(250000);
        Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);
        Sanctum::actingAs($student);

        $this->postJson('/api/cart/items', [
            'course_id' => $course->id,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('course_id', 'data.errors');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_student_cannot_add_a_course_already_in_the_cart(): void
    {
        $student = $this->createUser('student');
        $course = $this->createCourse(250000);
        Sanctum::actingAs($student);

        $this->postJson('/api/cart/items', [
            'course_id' => $course->id,
        ])->assertCreated();

        $this->postJson('/api/cart/items', [
            'course_id' => $course->id,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('course_id', 'data.errors');

        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_checkout_creates_order_and_price_snapshots_then_keeps_cart_while_pending(): void
    {
        $student = $this->createUser('student');
        $firstCourse = $this->createCourse(300000);
        $secondCourse = $this->createCourse(500000, 400000);
        Sanctum::actingAs($student);

        $this->postJson('/api/cart/items', ['course_id' => $firstCourse->id])->assertCreated();
        $this->postJson('/api/cart/items', ['course_id' => $secondCourse->id])->assertCreated();

        // Checkout must snapshot the current price, not the older cart snapshot.
        $firstCourse->update(['price' => 280000]);

        $response = $this->postJson('/api/checkout');

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total_amount', '680000.00')
            ->assertJsonCount(2, 'data.items');

        $this->assertMatchesRegularExpression('/^ORD\d{14}[A-Z0-9]{6}$/', $response->json('data.code'));
        $orderId = $response->json('data.id');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'user_id' => $student->id,
            'status' => 'pending',
            'total_amount' => 680000,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'course_id' => $firstCourse->id,
            'price' => 280000,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'course_id' => $secondCourse->id,
            'price' => 400000,
        ]);
        $this->assertDatabaseCount('order_items', 2);
        $this->assertDatabaseCount('cart_items', 2);

        $repeatedCheckout = $this->postJson('/api/checkout');

        $repeatedCheckout
            ->assertCreated()
            ->assertJsonPath('data.id', $orderId);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 2);
        $this->assertDatabaseCount('cart_items', 2);
    }

    public function test_checkout_creates_a_new_pending_order_when_cart_courses_change(): void
    {
        $student = $this->createUser('student');
        $firstCourse = $this->createCourse(250000);
        $secondCourse = $this->createCourse(150000);
        Sanctum::actingAs($student);

        $this->postJson('/api/cart/items', ['course_id' => $firstCourse->id])->assertCreated();
        $firstOrderId = $this->postJson('/api/checkout')->assertCreated()->json('data.id');

        $this->postJson('/api/cart/items', ['course_id' => $secondCourse->id])->assertCreated();
        $secondOrderId = $this->postJson('/api/checkout')->assertCreated()->json('data.id');

        $this->assertNotSame($firstOrderId, $secondOrderId);
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('cart_items', 2);
    }

    private function createUser(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst($roleName)]
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function createCourse(float $price, ?float $discountPrice = null): Course
    {
        $instructor = $this->createUser('instructor');
        $category = Category::query()->firstOrCreate(
            ['slug' => 'lap-trinh-web'],
            ['name' => 'Lập trình Web']
        );

        return Course::query()->create([
            'instructor_id' => $instructor->id,
            'category_id' => $category->id,
            'title' => 'Course '.str()->random(8),
            'slug' => 'course-'.str()->random(8),
            'price' => $price,
            'discount_price' => $discountPrice,
            'status' => 'approved',
            'published_at' => now(),
        ]);
    }
}
