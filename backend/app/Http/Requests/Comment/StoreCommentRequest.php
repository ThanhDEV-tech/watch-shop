<?php

namespace App\Http\Requests\Comment;

use Illuminate\Validation\Rule;

class StoreCommentRequest extends ViewLessonCommentsRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $lesson = $this->route('lesson');

        return [
            'content' => ['required', 'string', 'max:5000'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('comments', 'id')
                    ->where('lesson_id', $lesson?->id)
                    ->whereNull('deleted_at'),
            ],
        ];
    }
}
