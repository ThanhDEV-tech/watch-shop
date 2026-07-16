<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MyOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_own_paginated_orders_with_items(): void
    {
        $buyer = $this->createUser('student');
        $otherBuyer = $this->createUser('student');
        $instructor = $this->createUser('instructor');
        $category = Category::query()->create(['name' => 'Orders', 'slug' => 'orders']);
        $course = Course::query()->create([
            'instructor_id' => $instructor->id,
            'category_id' => $category->id,
            'title' => 'Order History Course',
            'slug' => 'order-history-course',
            'price' => 399000,
            'status' => 'approved',
        ]);
        $ownOrder = Order::query()->create([
            'user_id' => $buyer->id,
            'code' => 'MY-ORDER-001',
            'total_amount' => 399000,
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        $otherOrder = Order::query()->create([
            'user_id' => $otherBuyer->id,
            'code' => 'OTHER-ORDER-001',
            'total_amount' => 399000,
            'status' => 'paid',
        ]);
        $item = OrderItem::query()->create([
            'order_id' => $ownOrder->id,
            'course_id' => $course->id,
            'price' => 399000,
        ]);
        OrderItem::query()->create([
            'order_id' => $otherOrder->id,
            'course_id' => $course->id,
            'price' => 399000,
        ]);
        Sanctum::actingAs($buyer);

        $this->getJson('/api/my-orders?per_page=5')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $ownOrder->id)
            ->assertJsonPath('data.items.0.code', 'MY-ORDER-001')
            ->assertJsonPath('data.items.0.user.id', $buyer->id)
            ->assertJsonPath('data.items.0.items.0.id', $item->id)
            ->assertJsonPath('data.items.0.items.0.course.title', 'Order History Course')
            ->assertJsonMissing(['code' => 'OTHER-ORDER-001']);
    }

    public function test_user_can_filter_only_their_orders_by_status(): void
    {
        $buyer = $this->createUser('student');
        Order::query()->create([
            'user_id' => $buyer->id,
            'code' => 'PAID-ORDER',
            'total_amount' => 100000,
            'status' => 'paid',
        ]);
        Order::query()->create([
            'user_id' => $buyer->id,
            'code' => 'PENDING-ORDER',
            'total_amount' => 100000,
            'status' => 'pending',
        ]);
        Sanctum::actingAs($buyer);

        $this->getJson('/api/my-orders?status=paid')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.code', 'PAID-ORDER');
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
