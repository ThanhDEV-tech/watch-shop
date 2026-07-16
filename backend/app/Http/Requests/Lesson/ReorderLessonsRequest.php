<?php

namespace App\Http\Requests\Lesson;

use App\Http\Requests\CurriculumRequest;
use Illuminate\Validation\Rule;

class ReorderLessonsRequest extends CurriculumRequest
{
    public function authorize(): bool
    {
        return $this->canManageCourse($this->route('chapter')?->course);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $chapter = $this->route('chapter');

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('lessons', 'id')->where('chapter_id', $chapter?->id),
            ],
            'items.*.position' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }
}
