<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('gender_target', ['men', 'women', 'unisex']);
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('case_material')->nullable();
            $table->string('strap_material')->nullable();
            $table->string('glass_material')->nullable();
            $table->string('water_resistance')->nullable();
            $table->unsignedSmallInteger('warranty_months')->default(12);
            $table->text('warranty_note')->nullable();
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'gender_target']);
            $table->index(['brand_id', 'status']);
            $table->index(['category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
