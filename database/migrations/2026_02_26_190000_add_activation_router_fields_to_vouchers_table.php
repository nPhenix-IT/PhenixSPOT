<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->foreignId('activated_router_id')
                ->nullable()
                ->after('profile_id')
                ->constrained('routers')
                ->nullOnDelete();

            $table->string('activated_router_ip')->nullable()->after('activated_router_id');
            $table->string('activation_nas_identifier')->nullable()->after('activated_router_ip');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activated_router_id');
            $table->dropColumn(['activated_router_ip', 'activation_nas_identifier']);
        });
    }
};
