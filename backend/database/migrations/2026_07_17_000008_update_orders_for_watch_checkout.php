<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('receiver_name')->nullable()->after('user_id');
            $table->string('receiver_phone')->nullable()->after('receiver_name');
            $table->text('shipping_address')->nullable()->after('receiver_phone');
            $table->text('shipping_note')->nullable()->after('shipping_address');
            $table->string('shipping_zone_name')->nullable()->after('shipping_note');
            $table->decimal('shipping_fee', 12, 2)->default(0)->after('shipping_zone_name');
            $table->decimal('subtotal_amount', 12, 2)->default(0)->after('shipping_fee');
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
            $table->text('refund_note')->nullable()->after('refunded_at');
        });

        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending', 'paid', 'paid_stock_issue', 'failed', 'cancelled', 'shipping', 'completed', 'refunded') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending', 'paid', 'failed', 'cancelled') NOT NULL DEFAULT 'pending'");

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'receiver_name',
                'receiver_phone',
                'shipping_address',
                'shipping_note',
                'shipping_zone_name',
                'shipping_fee',
                'subtotal_amount',
                'refunded_at',
                'refund_note',
            ]);
        });
    }
};
