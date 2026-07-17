<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'user_id' => $this->user_id,
            'receiver_name' => $this->receiver_name,
            'receiver_phone' => $this->receiver_phone,
            'shipping_address' => $this->shipping_address,
            'shipping_note' => $this->shipping_note,
            'shipping_zone_name' => $this->shipping_zone_name,
            'shipping_fee' => $this->shipping_fee,
            'subtotal_amount' => $this->subtotal_amount,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'needs_attention' => $this->status === 'paid_stock_issue',
            'paid_at' => $this->paid_at,
            'refunded_at' => $this->refunded_at,
            'refund_note' => $this->refund_note,
            'created_at' => $this->created_at,
            'user' => new UserResource($this->whenLoaded('user')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
