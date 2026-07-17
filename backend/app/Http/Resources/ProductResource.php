<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class ProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $activeVariants = $this->whenLoaded('variants', fn () => $this->variants->where('is_active', true));
        $minFinalPrice = $activeVariants instanceof Collection
            ? $activeVariants
                ->map(fn ($variant) => (float) ($variant->discount_price ?? $variant->price))
                ->filter(fn ($price) => $price > 0)
                ->min()
            : null;
        $isOutOfStock = $activeVariants instanceof Collection
            ? ! $activeVariants->contains(fn ($variant) => $variant->stock_quantity > 0)
            : null;
        $productImages = collect();

        if ($this->resource->relationLoaded('images')) {
            $productImages = $this->images;
        } elseif ($this->resource->relationLoaded('productImages')) {
            $productImages = $this->productImages;
        }

        $normalizedProductImages = $productImages
            ->sortBy('display_order')
            ->map(fn ($image) => [
                'id' => $image->id,
                'image_path' => $image->image_path ?? $image->url ?? $image->path ?? null,
                'url' => $image->url ?? $image->image_path ?? $image->path ?? null,
                'alt_text' => $image->alt_text ?? $this->name,
                'display_order' => $image->display_order ?? 0,
                'is_primary' => (bool) ($image->is_primary ?? false),
            ])
            ->values();

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
            'min_final_price' => $minFinalPrice,
            'is_out_of_stock' => $isOutOfStock,
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'product_images' => $normalizedProductImages,
            'images' => $normalizedProductImages,
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'collections' => CollectionResource::collection($this->whenLoaded('collections')),
            'variants_count' => $this->whenCounted('variants'),
            'created_at' => $this->created_at,
        ];
    }
}
