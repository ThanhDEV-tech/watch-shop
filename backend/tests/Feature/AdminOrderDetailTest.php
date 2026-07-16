<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Role;
use App\Models\User;
use App\Models\VnpayTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminOrderDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_complete_order_detail_with_latest_vnpay_transaction(): void
    {
        $admin = $this->createUser('admin', 'Admin User');
        $student = $this->createUser('student', 'Student Buyer');
        $instructor = $this->createUser('instructor', 'Course Teacher');
        $category = Category::query()->create(['name' => 'Backend', 'slug' => 'backend']);
        $course = Course::query()->create([
            'instructor_id' => $instructor->id,
            'category_id' => $category->id,
            'title' => 'Laravel Order Course',
            'slug' => 'laravel-order-course',
            'thumbnail' => 'course-thumbnails/laravel.jpg',
            'price' => 499000,
            'status' => 'approved',
        ]);
        $order = Order::query()->create([
            'user_id' => $student->id,
            'code' => 'ORDER-DETAIL-001',
            'total_amount' => 399000,
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'course_id' => $course->id,
            'price' => 399000,
        ]);
        VnpayTransaction::query()->create([
            'order_id' => $order->id,
            'txn_ref' => $order->code,
            'amount' => 399000,
            'bank_code' => 'OLD',
            'transaction_no' => 'OLD-TRANSACTION',
            'response_code' => '24',
        ]);
        $latestTransaction = VnpayTransaction::query()->create([
            'order_id' => $order->id,
            'txn_ref' => $order->code,
            'amount' => 399000,
            'bank_code' => 'NCB',
            'transaction_no' => 'VNP123456',
            'response_code' => '00',
        ]);
        Sanctum::actingAs($admin);

        $this->getJson("/api/admin/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'ORDER-DETAIL-001')
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.total_amount', '399000.00')
            ->assertJsonPath('data.user.name', 'Student Buyer')
            ->assertJsonPath('data.user.email', $student->email)
            ->assertJsonPath('data.items.0.id', $item->id)
            ->assertJsonPath('data.items.0.course.title', 'Laravel Order Course')
            ->assertJsonPath('data.items.0.price_snapshot', '399000.00')
            ->assertJsonPath('data.items.0.course.thumbnail_url', url('/storage/course-thumbnails/laravel.jpg'))
            ->assertJsonPath('data.vnpay_transaction.bank_code', 'NCB')
            ->assertJsonPath('data.vnpay_transaction.response_code', '00')
            ->assertJsonPath('data.vnpay_transaction.transaction_no', $latestTransaction->transaction_no)
            ->assertJsonStructure(['data' => ['created_at', 'paid_at']]);
    }

    public function test_non_admin_cannot_view_order_detail(): void
    {
        $student = $this->createUser('student');
        $order = Order::query()->create([
            'user_id' => $student->id,
            'code' => 'PROTECTED-ORDER',
            'total_amount' => 100000,
            'status' => 'pending',
        ]);
        Sanctum::actingAs($student);

        $this->getJson("/api/admin/orders/{$order->id}")->assertForbidden();
    }

    public function test_missing_order_returns_clear_json_404(): void
    {
        Sanctum::actingAs($this->createUser('admin'));

        $this->getJson('/api/admin/orders/999999')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Không tìm thấy đơn hàng.');
    }

    private function createUser(string $roleName, string $name = 'Test User'): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName], ['display_name' => ucfirst($roleName)]);

        return User::factory()->create(['role_id' => $role->id, 'name' => $name]);
    }
}
