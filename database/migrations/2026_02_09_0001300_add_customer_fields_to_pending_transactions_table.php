<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_transactions', function (Blueprint $table) {
            $table->string('customer_name')->nullable()->after('profile_id');
            $table->string('customer_number')->nullable()->after('customer_name');
        });
    }

    public function down(): void
    {
        Schema::table('pending_transactions', function (Blueprint $table) {
            $table->dropColumn(['customer_name', 'customer_number']);
        });
    }
};
