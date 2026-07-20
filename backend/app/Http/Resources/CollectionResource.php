<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $today = now()->toDateString();
        $isCurrent = (! $this->start_date || $this->start_date->toDateString() <= $today)
            && (! $this->end_date || $this->end_date->toDateString() >= $today);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'is_current' => $isCurrent,
            'products_count' => $this->whenCounted('products'),
            'products' => $this->whenLoaded('products', fn () => $this->products
                ->values()
                ->map(fn ($product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'thumbnail' => $product->thumbnail,
                    'status' => $product->status,
                    'brand' => $product->relationLoaded('brand') ? new BrandResource($product->brand) : null,
                    'category' => $product->relationLoaded('category') ? new CategoryResource($product->category) : null,
                    'variants_count' => $product->variants_count ?? null,
                    'pivot' => [
                        'display_order' => $product->pivot->display_order ?? 0,
                    ],
                ])),
            'created_at' => $this->created_at,
        ];
    }
}
