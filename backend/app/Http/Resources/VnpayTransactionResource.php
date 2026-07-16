<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VnpayTransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'txn_ref' => $this->txn_ref,
            'amount' => $this->amount,
            'bank_code' => $this->bank_code,
            'transaction_no' => $this->transaction_no,
            'response_code' => $this->response_code,
            'transaction_status' => $this->transaction_status,
            'admin_id' => $this->admin_id,
            'is_verified' => $this->is_verified,
            'created_at' => $this->created_at,
            'order' => new OrderResource($this->whenLoaded('order')),
        ];
    }
}
