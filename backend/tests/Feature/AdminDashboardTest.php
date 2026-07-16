<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Role;
use App\Models\User;
use App\Models\VnpayTransaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_returns_exact_aggregated_statistics(): void
    {
        Carbon::setTestNow('2026-07-04 12:00:00');
        $admin = $this->createUser('admin', 'Dashboard Admin');
        $instructor = $this->createUser('instructor', 'Course Instructor');
        $this->createUser('student', 'First Student');
        $student = $this->createUser('student', 'Second Student');

        $this->createCourse($instructor, 'Pending Course', 'pending');
        $this->createCourse($instructor, 'Approved Course', 'approved');
        $this->createCourse($instructor, 'Rejected Course', 'rejected');
        $this->createCourse($instructor, 'Draft Course', 'draft');

        $this->createOrder($student, 'ORDER-PAID-TODAY', 300000, 'paid', now());
        $this->createOrder($student, 'ORDER-PAID-OLD', 200000, 'paid', now()->subDays(3));
        $this->createOrder($student, 'ORDER-PENDING', 900000, 'pending');
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_users', 4)
            ->assertJsonPath('data.total_students', 2)
            ->assertJsonPath('data.total_instructors', 1)
            ->assertJsonPath('data.total_courses.all', 4)
            ->assertJsonPath('data.total_courses.pending', 1)
            ->assertJsonPath('data.total_courses.approved', 1)
            ->assertJsonPath('data.total_courses.rejected', 1)
            ->assertJsonPath('data.total_orders', 3)
            ->assertJsonPath('data.total_revenue', 500000)
            ->assertJsonPath('data.pending_courses_count', 1)
            ->assertJsonCount(7, 'data.revenue_last_7_days');

        $revenue = collect($response->json('data.revenue_last_7_days'))->keyBy('date');
        $this->assertSame(300000, (int) $revenue['2026-07-04']['total']);
        $this->assertSame(200000, (int) $revenue['2026-07-01']['total']);
        $this->assertSame(0, (int) $revenue['2026-06-28']['total']);
    }

    public function test_admin_can_filter_users_and_cannot_lock_self(): void
    {
        $admin = $this->createUser('admin', 'Main Admin');
        $alice = $this->createUser('student', 'Alice Nguyen', 'alice@example.test');
        $this->createUser('student', 'Bob Nguyen', 'bob@example.test');
        $this->createUser('instructor', 'Alice Instructor', 'teacher@example.test');
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/users?role=student&search=alice&per_page=10')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.id', $alice->id)
            ->assertJsonPath('data.items.0.role.name', 'student');

        $this->patchJson("/api/admin/users/{$alice->id}/toggle-active")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
        $this->assertFalse($alice->fresh()->is_active);

        $this->patchJson("/api/admin/users/{$admin->id}/toggle-active")
            ->assertForbidden()
            ->assertJsonPath('success', false);
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_can_filter_orders_with_user_and_items(): void
    {
        $admin = $this->createUser('admin');
        $instructor = $this->createUser('instructor');
        $student = $this->createUser('student');
        $course = $this->createCourse($instructor, 'Order Course', 'approved');
        $paid = $this->createOrder($student, 'ORDER-FILTER-PAID', 275000, 'paid', now());
        OrderItem::query()->create(['order_id' => $paid->id, 'course_id' => $course->id, 'price' => 275000]);
        $this->createOrder($student, 'ORDER-FILTER-PENDING', 100000, 'pending');
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/orders?status=paid')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.id', $paid->id)
            ->assertJsonPath('data.items.0.user.id', $student->id)
            ->assertJsonPath('data.items.0.items.0.course_id', $course->id)
            ->assertJsonPath('data.items.0.items.0.price', '275000.00');
    }

    public function test_admin_can_filter_vnpay_transactions_with_related_order(): void
    {
        $admin = $this->createUser('admin');
        $student = $this->createUser('student');
        $order = $this->createOrder($student, 'ORDER-VNPAY', 400000, 'paid', now());
        $successful = $this->createTransaction($order, '00', 'TXN-SUCCESS');
        $this->createTransaction($order, '24', 'TXN-FAILED');
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/vnpay-transactions?response_code=00')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.id', $successful->id)
            ->assertJsonPath('data.items.0.response_code', '00')
            ->assertJsonPath('data.items.0.order.id', $order->id);
    }

    public function test_non_admin_is_forbidden_from_every_admin_dashboard_endpoint(): void
    {
        $student = $this->createUser('student');
        $target = $this->createUser('student');
        Sanctum::actingAs($student);

        foreach ([
            ['GET', '/api/admin/dashboard/stats'],
            ['GET', '/api/admin/users'],
            ['PATCH', "/api/admin/users/{$target->id}/toggle-active"],
            ['GET', '/api/admin/orders'],
            ['GET', '/api/admin/vnpay-transactions'],
        ] as [$method, $uri]) {
            $this->json($method, $uri)->assertForbidden();
        }
    }

    private function createUser(string $roleName, string $name = 'Test User', ?string $email = null): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst($roleName)],
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'name' => $name,
            'email' => $email ?? fake()->unique()->safeEmail(),
            'is_active' => true,
        ]);
    }

    private function createCourse(User $instructor, string $title, string $status): Course
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'dashboard-category'],
            ['name' => 'Dashboard Category'],
        );

        return Course::query()->create([
            'instructor_id' => $instructor->id,
            'category_id' => $category->id,
            'title' => $title,
            'slug' => str($title)->slug().'-'.fake()->unique()->numberBetween(1, 999999),
            'price' => 300000,
            'status' => $status,
        ]);
    }

    private function createOrder(User $user, string $code, int $amount, string $status, mixed $paidAt = null): Order
    {
        return Order::query()->create([
            'user_id' => $user->id,
            'code' => $code,
            'total_amount' => $amount,
            'status' => $status,
            'paid_at' => $paidAt,
        ]);
    }

    private function createTransaction(Order $order, string $responseCode, string $transactionNo): VnpayTransaction
    {
        return VnpayTransaction::query()->create([
            'order_id' => $order->id,
            'txn_ref' => $order->code,
            'amount' => $order->total_amount,
            'transaction_no' => $transactionNo,
            'response_code' => $responseCode,
            'transaction_status' => $responseCode,
            'raw_response' => [],
            'is_verified' => true,
        ]);
    }
}
