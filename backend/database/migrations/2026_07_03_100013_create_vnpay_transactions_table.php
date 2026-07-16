<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vnpay_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('txn_ref'); // vnp_TxnRef gửi sang VNPay
            $table->decimal('amount', 12, 2);
            $table->string('bank_code')->nullable();
            $table->string('transaction_no')->nullable(); // vnp_TransactionNo
            $table->string('response_code')->nullable(); // vnp_ResponseCode
            $table->string('secure_hash')->nullable();
            $table->json('raw_response')->nullable(); // toàn bộ query params VNPay trả về, để đối soát
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->index('txn_ref');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vnpay_transactions');
    }
};
