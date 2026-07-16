<?php

namespace App\Http\Requests\Chapter;

use App\Http\Requests\CurriculumRequest;

class UpdateChapterRequest extends CurriculumRequest
{
    public function authorize(): bool
    {
        return $this->canManageCourse($this->route('chapter')?->course);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
