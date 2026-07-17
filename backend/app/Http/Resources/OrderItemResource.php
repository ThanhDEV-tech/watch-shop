<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'product_name' => $this->product_name,
            'brand_name' => $this->brand_name,
            'sku' => $this->sku,
            'strap_color' => $this->strap_color,
            'dial_color' => $this->dial_color,
            'diameter_mm' => $this->diameter_mm,
            'movement_type' => $this->movement_type,
            'unit_price' => $this->unit_price,
            'quantity' => $this->quantity,
            'line_total' => $this->line_total,
            'thumbnail_url' => $this->thumbnail_url,
            'product' => new ProductResource($this->whenLoaded('product')),
            'product_variant' => new ProductVariantResource($this->whenLoaded('productVariant')),
        ];
    }
}
