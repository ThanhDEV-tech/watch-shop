<?php

namespace App\Http\Requests;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class CurriculumRequest extends FormRequest
{
    protected function canManageCourse(?Course $course): bool
    {
        $user = $this->user();

        return $user && $course && (
            $course->instructor_id === $user->id
            || $user->role?->name === 'admin'
        );
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'data' => null,
            'message' => 'Bạn không có quyền quản lý nội dung khóa học này.',
        ], 403));
    }
}
