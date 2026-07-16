<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VnpayTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'payment_id', 'admin_id', 'txn_ref', 'amount', 'bank_code', 'transaction_no',
        'response_code', 'transaction_status', 'secure_hash', 'raw_response', 'is_verified',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'raw_response' => 'array', 'is_verified' => 'boolean'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
