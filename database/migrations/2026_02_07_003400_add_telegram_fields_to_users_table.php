<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telegram_bot_token')->nullable()->after('sms_sender');
            $table->string('telegram_chat_id')->nullable()->after('telegram_bot_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telegram_bot_token', 'telegram_chat_id']);
        });
    }
};
