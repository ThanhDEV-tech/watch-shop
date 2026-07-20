<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'role' => ['nullable', Rule::in(['admin', 'customer'])],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([
                'pending',
                'paid',
                'paid_stock_issue',
                'failed',
                'cancelled',
                'shipping',
                'completed',
                'refunded',
            ])],
            'response_code' => ['nullable', 'string', 'max:10'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
