<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'type' => $this->type,
            'quantity_change' => $this->quantity_change,
            'stock_after' => $this->stock_after,
            'order_id' => $this->order_id,
            'note' => $this->note,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'product_variant' => new ProductVariantResource($this->whenLoaded('productVariant')),
            'order' => new OrderResource($this->whenLoaded('order')),
            'creator' => new UserResource($this->whenLoaded('creator')),
        ];
    }
}
