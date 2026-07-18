<?php

namespace App\Http\Requests\ShippingZone;

use Illuminate\Foundation\Http\FormRequest;

class StoreShippingZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'fee' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'display_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
