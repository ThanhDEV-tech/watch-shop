<?php

namespace App\Http\Requests\Chapter;

use App\Http\Requests\CurriculumRequest;

class StoreChapterRequest extends CurriculumRequest
{
    public function authorize(): bool
    {
        return $this->canManageCourse($this->route('course'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
