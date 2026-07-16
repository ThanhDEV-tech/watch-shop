<?php

namespace App\Http\Requests\Chapter;

use App\Http\Requests\CurriculumRequest;
use Illuminate\Validation\Rule;

class ReorderChaptersRequest extends CurriculumRequest
{
    public function authorize(): bool
    {
        return $this->canManageCourse($this->route('course'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $course = $this->route('course');

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('chapters', 'id')->where('course_id', $course?->id),
            ],
            'items.*.position' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }
}
