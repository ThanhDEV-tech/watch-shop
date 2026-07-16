<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_successfully_returns_token(): void
    {
        $role = Role::query()->create([
            'name' => 'student',
            'display_name' => 'Student',
        ]);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.user.role.name', 'student')
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_with_wrong_password_returns_unauthorized(): void
    {
        $role = Role::query()->create([
            'name' => 'student',
            'display_name' => 'Student',
        ]);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response
            ->assertUnauthorized()
            ->assertExactJson([
                'success' => false,
                'data' => null,
                'message' => 'Email hoặc mật khẩu không chính xác.',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_forgot_password_returns_generic_message_for_existing_and_missing_email(): void
    {
        $existingResponse = $this->postJson('/api/auth/forgot-password', [
            'email' => User::factory()->create()->email,
        ]);

        $missingResponse = $this->postJson('/api/auth/forgot-password', [
            'email' => 'missing@example.com',
        ]);

        $existingResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Nếu email tồn tại, link đặt lại mật khẩu đã được gửi');

        $missingResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Nếu email tồn tại, link đặt lại mật khẩu đã được gửi');
    }

    public function test_reset_password_updates_user_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('old-password'),
        ]);
        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Đặt lại mật khẩu thành công.');

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }
}
