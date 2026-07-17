<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand_id' => $this->brand_id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'gender_target' => $this->gender_target,
            'description' => $this->description,
            'content' => $this->content,
            'thumbnail' => $this->thumbnail,
            'case_material' => $this->case_material,
            'strap_material' => $this->strap_material,
            'glass_material' => $this->glass_material,
            'water_resistance' => $this->water_resistance,
            'warranty_months' => $this->warranty_months,
            'warranty_note' => $this->warranty_note,
            'status' => $this->status,
            'rating_avg' => $this->rating_avg,
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'collections' => CollectionResource::collection($this->whenLoaded('collections')),
            'variants_count' => $this->whenCounted('variants'),
            'created_at' => $this->created_at,
        ];
    }
}
