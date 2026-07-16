<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'reject_reason' => [
                Rule::requiredIf($this->input('status') === 'rejected'),
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
