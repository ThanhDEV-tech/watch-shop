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
                'course_id' => $item->course_id,
                'price_snapshot' => $item->price,
                'course' => $item->course ? [
                    'id' => $item->course->id,
                    'title' => $item->course->title,
                    'thumbnail_url' => $item->course->thumbnail
                        ? url(Storage::disk('public')->url($item->course->thumbnail))
                        : null,
                ] : null,
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
