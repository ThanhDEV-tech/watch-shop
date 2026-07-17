<?php

namespace App\Http\Requests\ProductVariant;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'product_id' => ['sometimes', 'required', 'integer', 'exists:products,id'],
            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('product_variants', 'sku')->ignore($this->route('productVariant')),
            ],
            'strap_color' => ['sometimes', 'required', 'string', 'max:255', Rule::in(ProductVariant::COLORS)],
            'dial_color' => ['sometimes', 'required', 'string', 'max:255', Rule::in(ProductVariant::COLORS)],
            'diameter_mm' => ['sometimes', 'required', 'integer', 'min:24', 'max:50'],
            'movement_type' => ['sometimes', 'required', Rule::in(ProductVariant::MOVEMENT_TYPES)],
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:50000000'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'max:50000000'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0'],
            'image' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
