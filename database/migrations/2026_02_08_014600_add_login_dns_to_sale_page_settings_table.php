<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_page_settings', function (Blueprint $table) {
            $table->string('login_dns')->nullable()->after('login_ticker_text');
        });
    }

    public function down(): void
    {
        Schema::table('sale_page_settings', function (Blueprint $table) {
            $table->dropColumn('login_dns');
        });
    }
};
