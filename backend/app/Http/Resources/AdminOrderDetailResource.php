<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AdminOrderDetailResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $transaction = $this->relationLoaded('vnpayTransactions')
            ? $this->vnpayTransactions->first()
            : null;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'receiver_name' => $this->receiver_name,
            'receiver_phone' => $this->receiver_phone,
            'shipping_address' => $this->shipping_address,
            'shipping_note' => $this->shipping_note,
            'shipping_zone_name' => $this->shipping_zone_name,
            'shipping_fee' => $this->shipping_fee,
            'subtotal_amount' => $this->subtotal_amount,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'paid_at' => $this->paid_at,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $item->product_name,
                'brand_name' => $item->brand_name,
                'sku' => $item->sku,
                'strap_color' => $item->strap_color,
                'dial_color' => $item->dial_color,
                'diameter_mm' => $item->diameter_mm,
                'movement_type' => $item->movement_type,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'line_total' => $item->line_total,
                'thumbnail_url' => $item->thumbnail_url
                    ? url(Storage::disk('public')->url($item->thumbnail_url))
                    : null,
            ])->values()),
            'vnpay_transaction' => $transaction ? [
                'admin_id' => $transaction->admin_id,
                'bank_code' => $transaction->bank_code,
                'response_code' => $transaction->response_code,
                'transaction_no' => $transaction->transaction_no,
            ] : null,
        ];
    }
}
