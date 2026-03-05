<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('pending_transactions') || !Schema::hasColumn('pending_transactions', 'commission_payer')) {
            return;
        }

        Schema::table('pending_transactions', function (Blueprint $table) {
            $table->string('commission_payer', 20)->default('seller')->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pending_transactions') || !Schema::hasColumn('pending_transactions', 'commission_payer')) {
            return;
        }

        Schema::table('pending_transactions', function (Blueprint $table) {
            $table->enum('commission_payer', ['seller', 'client'])->default('seller')->change();
        });
    }
};
