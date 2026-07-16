<?php

namespace App\Http\Requests\Comment;

use Illuminate\Foundation\Http\FormRequest;

class ViewLessonCommentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lesson = $this->route('lesson');
        $courseId = $lesson?->chapter?->course_id;

        return $this->user()?->enrollments()
            ->where('course_id', $courseId)
            ->exists() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
        ];
    }
}
