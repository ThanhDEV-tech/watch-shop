<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['reviews', 'cart_items', 'order_items'] as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'course_id')) {
                continue;
            }

            try {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropForeign(['course_id']);
                });
            } catch (Throwable) {
                //
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('course_id');
            });
        }

        Schema::dropIfExists('courses');
    }

    public function down(): void
    {
        if (Schema::hasTable('courses')) {
            return;
        }

        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->json('requirements')->nullable();
            $table->string('thumbnail')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('discount_price', 12, 2)->nullable();
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
            $table->text('reject_reason')->nullable();
            $table->unsignedInteger('total_students')->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
