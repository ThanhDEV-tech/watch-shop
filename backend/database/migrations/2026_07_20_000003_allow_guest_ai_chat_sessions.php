<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_chat_sessions')) {
            return;
        }

        Schema::table('ai_chat_sessions', function (Blueprint $table): void {
            if (! Schema::hasColumn('ai_chat_sessions', 'guest_token')) {
                $table->string('guest_token', 100)->nullable()->unique()->after('user_id');
            }
        });

        try {
            Schema::table('ai_chat_sessions', function (Blueprint $table): void {
                $table->dropForeign(['user_id']);
            });
        } catch (Throwable) {
            //
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE ai_chat_sessions MODIFY user_id BIGINT UNSIGNED NULL');
        } else {
            Schema::table('ai_chat_sessions', function (Blueprint $table): void {
                $table->foreignId('user_id')->nullable()->change();
            });
        }

        try {
            Schema::table('ai_chat_sessions', function (Blueprint $table): void {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        } catch (Throwable) {
            //
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_chat_sessions')) {
            return;
        }

        DB::table('ai_chat_sessions')->whereNull('user_id')->delete();

        try {
            Schema::table('ai_chat_sessions', function (Blueprint $table): void {
                $table->dropForeign(['user_id']);
            });
        } catch (Throwable) {
            //
        }

        if (Schema::hasColumn('ai_chat_sessions', 'guest_token')) {
            Schema::table('ai_chat_sessions', function (Blueprint $table): void {
                $table->dropUnique('ai_chat_sessions_guest_token_unique');
                $table->dropColumn('guest_token');
            });
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE ai_chat_sessions MODIFY user_id BIGINT UNSIGNED NOT NULL');
        }

        Schema::table('ai_chat_sessions', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
