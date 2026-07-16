<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class DeleteReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $review = $this->route('review');

        return $user && $review && (
            $review->user_id === $user->id
            || $user->role?->name === 'admin'
        );
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
