<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Certification;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCertificationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_certifications_and_regenerate_badge(): void
    {
        Storage::fake('public');
        Http::fake([
            '*' => Http::response('fake-image-content', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        Sanctum::actingAs($this->createUser('admin'));

        $createResponse = $this->postJson('/api/admin/certifications', [
            'name' => 'AWS Certified Developer',
            'provider' => 'AWS',
            'description' => 'Cloud certification path.',
            'icon' => 'cloud_done',
            'accent_color' => '#FF9900',
            'exam_info' => 'Exam overview',
            'external_link' => 'https://aws.amazon.com/certification/',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'AWS Certified Developer')
            ->assertJsonPath('data.accent_color', '#FF9900');

        $certificationId = $createResponse->json('data.id');

        $this->getJson('/api/admin/certifications')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items.0.id', $certificationId);

        $this->putJson("/api/admin/certifications/{$certificationId}", [
            'name' => 'AWS Certified Solutions Architect',
            'provider' => 'AWS',
            'accent_color' => '#FF6B4A',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'AWS Certified Solutions Architect')
            ->assertJsonPath('data.accent_color', '#FF6B4A');

        $this->postJson("/api/admin/certifications/{$certificationId}/regenerate-badge")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.badge_image_url', url('/storage/certification-badges/'.$certificationId.'.jpg'));

        Storage::disk('public')->assertExists("certification-badges/{$certificationId}.jpg");

        $this->deleteJson("/api/admin/certifications/{$certificationId}")
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_non_admin_cannot_access_admin_certification_crud(): void
    {
        Sanctum::actingAs($this->createUser('student'));

        $this->getJson('/api/admin/certifications')->assertForbidden();
        $this->postJson('/api/admin/certifications', [
            'name' => 'Blocked',
            'provider' => 'Test',
        ])->assertForbidden();
    }

    public function test_admin_cannot_delete_certification_that_has_courses(): void
    {
        $admin = $this->createUser('admin');
        $instructor = $this->createUser('instructor');
        $category = Category::query()->create(['name' => 'Cloud', 'slug' => 'cloud']);
        $certification = Certification::query()->create([
            'name' => 'AWS Certified',
            'provider' => 'AWS',
            'description' => 'Cloud credential.',
            'accent_color' => '#FF9900',
            'exam_info' => 'Exam details.',
        ]);
        $course = Course::query()->create([
            'instructor_id' => $instructor->id,
            'category_id' => $category->id,
            'title' => 'AWS Cloud Course',
            'slug' => 'aws-cloud-course',
            'price' => 499000,
            'status' => 'approved',
        ]);
        $certification->courses()->attach($course);

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/admin/certifications/{$certification->id}")
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Không thể xóa chứng chỉ đang có khóa học liên quan.');
    }

    private function createUser(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName], ['display_name' => ucfirst($roleName)]);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
