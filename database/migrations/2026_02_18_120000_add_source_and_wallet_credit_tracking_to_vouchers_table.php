<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('source', 32)->default('manual_generation')->after('is_active');
            $table->timestamp('wallet_credited_at')->nullable()->after('used_at');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn(['source', 'wallet_credited_at']);
        });
    }
};
