<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'strap_color' => $this->strap_color,
            'dial_color' => $this->dial_color,
            'diameter_mm' => $this->diameter_mm,
            'movement_type' => $this->movement_type,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'final_price' => $this->final_price,
            'stock_quantity' => $this->stock_quantity,
            'image' => $this->image,
            'is_active' => $this->is_active,
            'product' => new ProductResource($this->whenLoaded('product')),
            'created_at' => $this->created_at,
        ];
    }
}
