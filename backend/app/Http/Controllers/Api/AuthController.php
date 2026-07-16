<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\UploadAvatarRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const PASSWORD_RESET_SENT_MESSAGE = 'Nếu email tồn tại, link đặt lại mật khẩu đã được gửi';

    public function register(RegisterRequest $request): JsonResponse
    {
        $studentRole = Role::query()->where('name', 'student')->firstOrFail();

        $user = User::query()->create([
            ...$request->safe()->only(['name', 'email', 'password']),
            'role_id' => $studentRole->id,
        ]);

        $user->load('role');

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'token' => $user->createToken('auth-token')->plainTextToken,
            ],
            'message' => 'Đăng ký thành công.',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->with('role')
            ->where('email', $request->validated('email'))
            ->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Email hoặc mật khẩu không chính xác.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'token' => $user->createToken('auth-token')->plainTextToken,
            ],
            'message' => 'Đăng nhập thành công.',
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->safe()->only('email'));

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => self::PASSWORD_RESET_SENT_MESSAGE,
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->safe()->only(['email', 'password', 'password_confirmation', 'token']),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.'],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Đặt lại mật khẩu thành công.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Đăng xuất thành công.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($request->user()->load('role')),
            'message' => 'Lấy thông tin người dùng thành công.',
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => new UserResource($user->refresh()->load('role')),
            'message' => 'Cập nhật thông tin cá nhân thành công.',
        ]);
    }

    public function updateAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = $request->user();
        $oldAvatar = $user->avatar;
        $newAvatar = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar' => $newAvatar]);

        if ($oldAvatar && ! Str::startsWith($oldAvatar, ['http://', 'https://'])) {
            Storage::disk('public')->delete($oldAvatar);
        }

        $user = $user->refresh()->load('role');

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
            'message' => 'Cập nhật ảnh đại diện thành công.',
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        if (! Hash::check($request->validated('current_password'), $request->user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Mật khẩu hiện tại không chính xác.'],
            ]);
        }

        $request->user()->update([
            'password' => $request->validated('new_password'),
        ]);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Đổi mật khẩu thành công.',
        ]);
    }
}
