<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_and_update_profile_without_changing_email(): void
    {
        $user = $this->createUser([
            'name' => 'Old Name',
            'phone' => '0900000000',
            'avatar' => 'avatars/current.jpg',
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.phone', '0900000000')
            ->assertJsonPath('data.avatar', url('/storage/avatars/current.jpg'))
            ->assertJsonPath('data.avatar_url', url('/storage/avatars/current.jpg'));

        $this->putJson('/api/auth/profile', [
            'name' => 'Updated Name',
            'phone' => '0911222333',
            'email' => 'forbidden-change@example.test',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.phone', '0911222333')
            ->assertJsonPath('data.email', $user->email);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'phone' => '0911222333',
            'email' => $user->email,
        ]);
    }

    public function test_wrong_current_password_is_rejected_and_correct_password_can_be_changed(): void
    {
        $user = $this->createUser(['password' => Hash::make('old-password')]);
        Sanctum::actingAs($user);

        $this->putJson('/api/auth/password', [
            'current_password' => 'wrong-password',
            'new_password' => 'new-password',
            'new_password_confirmation' => 'new-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('current_password', 'data.errors');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));

        $this->putJson('/api/auth/password', [
            'current_password' => 'old-password',
            'new_password' => 'new-password',
            'new_password_confirmation' => 'new-password',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_uploading_avatar_replaces_and_deletes_previous_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/old-avatar.jpg', 'old-avatar');
        $user = $this->createUser(['avatar' => 'avatars/old-avatar.jpg']);
        Sanctum::actingAs($user);

        $response = $this->post('/api/auth/avatar', [
            'avatar' => $this->fakePng('new-avatar.png'),
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['avatar', 'avatar_url']]);

        $newPath = $user->fresh()->avatar;
        $this->assertStringStartsWith('avatars/', $newPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing('avatars/old-avatar.jpg');
    }

    public function test_avatar_rejects_invalid_format_and_files_larger_than_two_megabytes(): void
    {
        Storage::fake('public');
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $this->post('/api/auth/avatar', [
            'avatar' => UploadedFile::fake()->create('avatar.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('avatar', 'data.errors');

        $this->post('/api/auth/avatar', [
            'avatar' => $this->fakePng('oversized.png', 2049),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('avatar', 'data.errors');

        $this->assertNull($user->fresh()->avatar);
    }

    /** @param array<string, mixed> $attributes */
    private function createUser(array $attributes = []): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => 'student'],
            ['display_name' => 'Student'],
        );

        return User::factory()->create([
            'role_id' => $role->id,
            ...$attributes,
        ]);
    }

    private function fakePng(string $name, int $kilobytes = 1): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );
        $content = $png.str_repeat("\0", max(0, ($kilobytes * 1024) - strlen($png)));

        return UploadedFile::fake()->createWithContent($name, $content);
    }
}
