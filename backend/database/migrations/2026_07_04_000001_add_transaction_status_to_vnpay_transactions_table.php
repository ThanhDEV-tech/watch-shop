<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vnpay_transactions', function (Blueprint $table) {
            $table->string('transaction_status')->nullable()->after('response_code');
        });
    }

    public function down(): void
    {
        Schema::table('vnpay_transactions', function (Blueprint $table) {
            $table->dropColumn('transaction_status');
        });
    }
};
