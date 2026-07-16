<?php

namespace App\Http\Requests\Lesson;

use App\Http\Requests\CurriculumRequest;

class StoreLessonRequest extends CurriculumRequest
{
    public function authorize(): bool
    {
        return $this->canManageCourse($this->route('chapter')?->course);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'duration_seconds' => ['sometimes', 'integer', 'min:0'],
            'position' => ['sometimes', 'integer', 'min:0'],
            'is_free_preview' => ['sometimes', 'boolean'],
        ];
    }
}
