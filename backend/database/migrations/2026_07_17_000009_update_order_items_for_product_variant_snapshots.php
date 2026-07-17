<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropForeign(['course_id']);
            });
        } catch (Throwable) {
            //
        }

        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'product_id')) {
                $table->foreignId('product_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('order_items', 'product_variant_id')) {
                $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('order_items', 'product_name')) {
                $table->string('product_name')->nullable()->after('product_variant_id');
            }

            if (! Schema::hasColumn('order_items', 'brand_name')) {
                $table->string('brand_name')->nullable()->after('product_name');
            }

            if (! Schema::hasColumn('order_items', 'sku')) {
                $table->string('sku')->nullable()->after('brand_name');
            }

            if (! Schema::hasColumn('order_items', 'strap_color')) {
                $table->string('strap_color')->nullable()->after('sku');
            }

            if (! Schema::hasColumn('order_items', 'dial_color')) {
                $table->string('dial_color')->nullable()->after('strap_color');
            }

            if (! Schema::hasColumn('order_items', 'diameter_mm')) {
                $table->unsignedTinyInteger('diameter_mm')->nullable()->after('dial_color');
            }

            if (! Schema::hasColumn('order_items', 'movement_type')) {
                $table->string('movement_type')->nullable()->after('diameter_mm');
            }

            if (! Schema::hasColumn('order_items', 'unit_price')) {
                $table->decimal('unit_price', 12, 2)->default(0)->after('movement_type');
            }

            if (! Schema::hasColumn('order_items', 'quantity')) {
                $table->unsignedInteger('quantity')->default(1)->after('unit_price');
            }

            if (! Schema::hasColumn('order_items', 'line_total')) {
                $table->decimal('line_total', 12, 2)->default(0)->after('quantity');
            }

            if (! Schema::hasColumn('order_items', 'thumbnail_url')) {
                $table->string('thumbnail_url')->nullable()->after('line_total');
            }

            if (Schema::hasColumn('order_items', 'course_id')) {
                $table->dropColumn('course_id');
            }

            if (Schema::hasColumn('order_items', 'price')) {
                $table->dropColumn('price');
            }
        });

        try {
            Schema::table('order_items', function (Blueprint $table) {
                $table->index('product_variant_id');
            });
        } catch (Throwable) {
            //
        }
    }

    public function down(): void
    {
        try {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
                $table->dropForeign(['product_variant_id']);
            });
        } catch (Throwable) {
            //
        }

        try {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropIndex(['product_variant_id']);
            });
        } catch (Throwable) {
            //
        }

        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'course_id')) {
                $table->foreignId('course_id')->nullable()->after('order_id')->constrained();
            }

            if (! Schema::hasColumn('order_items', 'price')) {
                $table->decimal('price', 12, 2)->default(0)->after('course_id');
            }

            foreach ([
                'product_id',
                'product_variant_id',
                'product_name',
                'brand_name',
                'sku',
                'strap_color',
                'dial_color',
                'diameter_mm',
                'movement_type',
                'unit_price',
                'quantity',
                'line_total',
                'thumbnail_url',
            ] as $column) {
                if (Schema::hasColumn('order_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
