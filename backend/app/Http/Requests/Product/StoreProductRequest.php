<?php

namespace App\Http\Requests\Product;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'gender_target' => ['required', Rule::in(Product::GENDER_TARGETS)],
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
