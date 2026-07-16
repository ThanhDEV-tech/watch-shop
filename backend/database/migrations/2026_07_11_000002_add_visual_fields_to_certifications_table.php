<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certifications', function (Blueprint $table) {
            $table->string('accent_color', 7)->default('#FF6B4A')->after('icon');
            $table->string('badge_image')->nullable()->after('accent_color');
        });
    }

    public function down(): void
    {
        Schema::table('certifications', function (Blueprint $table) {
            $table->dropColumn(['accent_color', 'badge_image']);
        });
    }
};
