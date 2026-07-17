<?php

namespace App\Http\Requests\ProductVariant;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:product_variants,sku'],
            'strap_color' => ['required', 'string', 'max:255', Rule::in(ProductVariant::COLORS)],
            'dial_color' => ['required', 'string', 'max:255', Rule::in(ProductVariant::COLORS)],
            'diameter_mm' => ['required', 'integer', 'min:24', 'max:50'],
            'movement_type' => ['required', Rule::in(ProductVariant::MOVEMENT_TYPES)],
            'price' => ['required', 'numeric', 'min:0', 'max:50000000'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'max:50000000', 'lte:price'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0'],
            'image' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
