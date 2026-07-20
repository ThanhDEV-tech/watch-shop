<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reviews')) {
            return;
        }

        if (Schema::hasColumn('reviews', 'course_id')) {
            DB::table('reviews')->delete();

            try {
                Schema::table('reviews', function (Blueprint $table): void {
                    $table->dropUnique('reviews_user_id_course_id_unique');
                });
            } catch (Throwable) {
                //
            }

            try {
                Schema::table('reviews', function (Blueprint $table): void {
                    $table->dropForeign(['course_id']);
                });
            } catch (Throwable) {
                //
            }

            Schema::table('reviews', function (Blueprint $table): void {
                $table->dropColumn('course_id');
            });
        }

        Schema::table('reviews', function (Blueprint $table): void {
            if (! Schema::hasColumn('reviews', 'product_id')) {
                $table->foreignId('product_id')->after('user_id')->constrained()->cascadeOnDelete();
            }
        });

        try {
            Schema::table('reviews', function (Blueprint $table): void {
                $table->unique(['user_id', 'product_id']);
            });
        } catch (Throwable) {
            //
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('reviews')) {
            return;
        }

        try {
            Schema::table('reviews', function (Blueprint $table): void {
                $table->dropUnique('reviews_user_id_product_id_unique');
            });
        } catch (Throwable) {
            //
        }

        try {
            Schema::table('reviews', function (Blueprint $table): void {
                $table->dropForeign(['product_id']);
            });
        } catch (Throwable) {
            //
        }

        Schema::table('reviews', function (Blueprint $table): void {
            if (Schema::hasColumn('reviews', 'product_id')) {
                $table->dropColumn('product_id');
            }

            if (! Schema::hasColumn('reviews', 'course_id')) {
                $table->foreignId('course_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
            }
        });

        try {
            Schema::table('reviews', function (Blueprint $table): void {
                $table->unique(['user_id', 'course_id']);
            });
        } catch (Throwable) {
            //
        }
    }
};
