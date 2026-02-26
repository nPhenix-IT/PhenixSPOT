<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('withdrawal_requests', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            if (Schema::hasColumn('withdrawal_requests', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
        });
    }
};
