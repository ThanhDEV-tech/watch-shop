<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropForeign(['course_id']);
            });
        } catch (Throwable) {
            //
        }

        try {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropUnique('cart_items_cart_id_course_id_unique');
            });
        } catch (Throwable) {
            //
        }

        Schema::table('cart_items', function (Blueprint $table) {
            if (! Schema::hasColumn('cart_items', 'product_variant_id')) {
                $table->foreignId('product_variant_id')
                    ->nullable()
                    ->after('cart_id')
                    ->constrained()
                    ->cascadeOnDelete();
            }

            if (! Schema::hasColumn('cart_items', 'quantity')) {
                $table->unsignedInteger('quantity')->default(1)->after('product_variant_id');
            }

            if (Schema::hasColumn('cart_items', 'course_id')) {
                $table->dropColumn('course_id');
            }

            if (Schema::hasColumn('cart_items', 'price_snapshot')) {
                $table->dropColumn('price_snapshot');
            }
        });

        try {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->unique(['cart_id', 'product_variant_id']);
            });
        } catch (Throwable) {
            //
        }
    }

    public function down(): void
    {
        try {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropUnique('cart_items_cart_id_product_variant_id_unique');
            });
        } catch (Throwable) {
            //
        }

        try {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropForeign(['product_variant_id']);
            });
        } catch (Throwable) {
            //
        }

        Schema::table('cart_items', function (Blueprint $table) {
            if (! Schema::hasColumn('cart_items', 'course_id')) {
                $table->foreignId('course_id')
                    ->nullable()
                    ->after('cart_id')
                    ->constrained()
                    ->cascadeOnDelete();
            }

            if (! Schema::hasColumn('cart_items', 'price_snapshot')) {
                $table->decimal('price_snapshot', 12, 2)->default(0)->after('course_id');
            }

            if (Schema::hasColumn('cart_items', 'product_variant_id')) {
                $table->dropColumn('product_variant_id');
            }

            if (Schema::hasColumn('cart_items', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });

        try {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->unique(['cart_id', 'course_id']);
            });
        } catch (Throwable) {
            //
        }
    }
};
