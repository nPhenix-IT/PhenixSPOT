<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_transactions', function (Blueprint $table) {
            $table->enum('commission_payer', ['seller', 'client'])->default('seller')->after('customer_number');
            $table->decimal('commission_amount', 8, 2)->default(0)->after('commission_payer');
            $table->decimal('total_price', 8, 2)->default(0)->after('commission_amount');
        });
    }

    public function down(): void
    {
        Schema::table('pending_transactions', function (Blueprint $table) {
            $table->dropColumn(['commission_payer', 'commission_amount', 'total_price']);
        });
    }
};
