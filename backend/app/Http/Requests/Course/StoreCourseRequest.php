<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'price' => ['required', 'numeric', 'min:0', 'max:50000000'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'max:50000000', 'lte:price'],
            'level' => ['sometimes', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'sync_certifications' => ['sometimes', 'boolean'],
            'certification_ids' => ['nullable', 'array'],
            'certification_ids.*' => ['integer', 'exists:certifications,id'],
        ];
    }
}
