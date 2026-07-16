<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_lists_active_and_inactive_categories_with_course_counts(): void
    {
        $admin = $this->createUser('admin');
        $instructor = $this->createUser('instructor');
        $used = Category::query()->create(['name' => 'Used', 'slug' => 'used', 'is_active' => true]);
        Category::query()->create(['name' => 'Hidden', 'slug' => 'hidden', 'is_active' => false]);
        Course::query()->create([
            'instructor_id' => $instructor->id,
            'category_id' => $used->id,
            'title' => 'Category Course',
            'slug' => 'category-course',
            'price' => 200000,
            'status' => 'draft',
        ]);
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/categories')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 2)
            ->assertJsonCount(2, 'data.items');

        $categories = collect($response->json('data.items'))->keyBy('slug');
        $this->assertSame(1, $categories['used']['courses_count']);
        $this->assertFalse($categories['hidden']['is_active']);
    }

    public function test_admin_can_create_category_with_generated_slug_and_duplicate_slug_is_rejected(): void
    {
        Sanctum::actingAs($this->createUser('admin'));

        $this->postJson('/api/admin/categories', [
            'name' => 'Backend Development',
            'description' => 'Backend courses',
            'icon' => 'terminal',
        ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'backend-development');

        $this->postJson('/api/admin/categories', [
            'name' => 'Duplicate Backend',
            'slug' => 'backend-development',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('slug', 'data.errors');
    }

    public function test_admin_can_update_category(): void
    {
        $admin = $this->createUser('admin');
        $category = Category::query()->create(['name' => 'Old Name', 'slug' => 'old-name']);
        Sanctum::actingAs($admin);

        $this->putJson("/api/admin/categories/{$category->id}", [
            'name' => 'New Name',
            'description' => 'Updated description',
            'is_active' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.slug', 'new-name')
            ->assertJsonPath('data.is_active', false);
    }

    public function test_public_categories_endpoint_only_returns_active_categories(): void
    {
        Category::query()->create(['name' => 'Visible', 'slug' => 'visible', 'is_active' => true]);
        Category::query()->create(['name' => 'Hidden', 'slug' => 'hidden', 'is_active' => false]);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'visible');
    }

    public function test_admin_can_toggle_category_active_state_without_deleting_it(): void
    {
        $admin = $this->createUser('admin');
        $category = Category::query()->create(['name' => 'Toggle Me', 'slug' => 'toggle-me', 'is_active' => true]);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/categories/{$category->id}/toggle-active")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'is_active' => false]);

        $this->patchJson("/api/admin/categories/{$category->id}/toggle-active")
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'is_active' => true]);
    }

    public function test_category_with_courses_cannot_be_deleted_but_empty_category_can(): void
    {
        $admin = $this->createUser('admin');
        $instructor = $this->createUser('instructor');
        $used = Category::query()->create(['name' => 'Used', 'slug' => 'used']);
        $empty = Category::query()->create(['name' => 'Empty', 'slug' => 'empty']);
        Course::query()->create([
            'instructor_id' => $instructor->id,
            'category_id' => $used->id,
            'title' => 'Existing Course',
            'slug' => 'existing-course',
            'price' => 100000,
            'status' => 'draft',
        ]);
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/admin/categories/{$used->id}")
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Không thể xóa danh mục đang có khóa học.');
        $this->assertDatabaseHas('categories', ['id' => $used->id]);

        $this->deleteJson("/api/admin/categories/{$empty->id}")
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseMissing('categories', ['id' => $empty->id]);
    }

    public function test_non_admin_cannot_access_admin_category_management(): void
    {
        $student = $this->createUser('student');
        $category = Category::query()->create(['name' => 'Protected', 'slug' => 'protected']);
        Sanctum::actingAs($student);

        foreach ([
            ['GET', '/api/admin/categories', []],
            ['POST', '/api/admin/categories', ['name' => 'No Access']],
            ['PUT', "/api/admin/categories/{$category->id}", ['name' => 'No Access']],
            ['DELETE', "/api/admin/categories/{$category->id}", []],
        ] as [$method, $uri, $data]) {
            $this->json($method, $uri, $data)->assertForbidden();
        }
    }

    private function createUser(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName], ['display_name' => ucfirst($roleName)]);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
