<?php

namespace App\Http\Requests\Comment;

use Illuminate\Foundation\Http\FormRequest;

class DeleteCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $comment = $this->route('comment');

        return $user && $comment && (
            $comment->user_id === $user->id
            || $user->role?->name === 'admin'
        );
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
