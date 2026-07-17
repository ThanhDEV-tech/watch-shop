<?php

namespace App\Http\Requests\Product;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'brand_id' => ['sometimes', 'required', 'integer', 'exists:brands,id'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($this->route('product')),
            ],
            'gender_target' => ['sometimes', 'required', Rule::in(Product::GENDER_TARGETS)],
            'description' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'string', 'max:255'],
            'case_material' => ['nullable', 'string', 'max:255'],
            'strap_material' => ['nullable', 'string', 'max:255'],
            'glass_material' => ['nullable', 'string', 'max:255'],
            'water_resistance' => ['nullable', 'string', 'max:255'],
            'warranty_months' => ['sometimes', 'integer', 'min:0', 'max:240'],
            'warranty_note' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(Product::STATUSES)],
            'rating_avg' => ['sometimes', 'numeric', 'min:0', 'max:5'],
            'collection_ids' => ['sometimes', 'array'],
            'collection_ids.*' => ['integer', 'exists:collections,id'],
        ];
    }
}
