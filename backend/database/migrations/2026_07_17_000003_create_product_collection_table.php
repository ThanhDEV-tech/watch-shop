<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_collection', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'collection_id']);
            $table->index('product_id');
            $table->index(['collection_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_collection');
    }
};
