<?php

namespace Tests\Feature;

use App\Mail\OrderPaidMail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Role;
use App\Models\User;
use App\Models\VnpayTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminManualPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_mark_pending_order_as_paid_and_audit_transaction(): void
    {
        Mail::fake();

        [$admin, $student, $course, $order] = $this->createPendingOrder();
        $cart = Cart::query()->create(['user_id' => $student->id]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'course_id' => $course->id,
            'price_snapshot' => 499000,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/admin/orders/{$order->id}/mark-as-paid")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.vnpay_transaction.admin_id', $admin->id)
            ->assertJsonPath('data.vnpay_transaction.bank_code', 'MANUAL_ADMIN')
            ->assertJsonPath('data.vnpay_transaction.response_code', '00');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('vnpay_transactions', [
            'order_id' => $order->id,
            'admin_id' => $admin->id,
            'bank_code' => 'MANUAL_ADMIN',
            'transaction_status' => 'MANUAL_ADMIN',
        ]);
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'order_id' => $order->id,
        ]);
        $this->assertDatabaseMissing('cart_items', [
            'cart_id' => $cart->id,
            'course_id' => $course->id,
        ]);
        Mail::assertQueued(OrderPaidMail::class);
    }

    public function test_admin_cannot_mark_paid_order_as_paid_again(): void
    {
        $data = $this->createPendingOrder(['status' => 'paid', 'paid_at' => now()]);
        $admin = $data[0];
        $order = $data[3];

        Sanctum::actingAs($admin);

        $this->postJson("/api/admin/orders/{$order->id}/mark-as-paid")
            ->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Chỉ có thể đánh dấu đã thanh toán cho đơn hàng đang pending.');

        $this->assertSame(0, VnpayTransaction::query()->where('order_id', $order->id)->count());
    }

    public function test_non_admin_cannot_mark_order_as_paid(): void
    {
        $data = $this->createPendingOrder();
        $student = $data[1];
        $order = $data[3];

        Sanctum::actingAs($student);

        $this->postJson("/api/admin/orders/{$order->id}/mark-as-paid")->assertForbidden();
    }

    /** @return array{0: User, 1: User, 2: Course, 3: Order} */
    private function createPendingOrder(array $orderOverrides = []): array
    {
        $admin = $this->createUser('admin');
        $student = $this->createUser('student');
        $instructor = $this->createUser('instructor');
        $category = Category::query()->create(['name' => 'Backend', 'slug' => 'backend']);
        $course = Course::query()->create([
            'instructor_id' => $instructor->id,
            'category_id' => $category->id,
            'title' => 'Laravel Payment Course',
            'slug' => 'laravel-payment-course',
            'price' => 499000,
            'status' => 'approved',
        ]);
        $order = Order::query()->create(array_merge([
            'user_id' => $student->id,
            'code' => 'MANUAL-PAID-001',
            'total_amount' => 499000,
            'status' => 'pending',
        ], $orderOverrides));

        OrderItem::query()->create([
            'order_id' => $order->id,
            'course_id' => $course->id,
            'price' => 499000,
        ]);

        return [$admin, $student, $course, $order];
    }

    private function createUser(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName], ['display_name' => ucfirst($roleName)]);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
