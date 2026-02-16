<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vpn_servers', function (Blueprint $table) {
            if (!Schema::hasColumn('vpn_servers', 'profile_name')) {
                $table->string('profile_name')->nullable()->after('domain_name');
            }
            if (!Schema::hasColumn('vpn_servers', 'gateway_ip')) {
                $table->string('gateway_ip')->nullable()->after('local_ip_address');
            }
            if (!Schema::hasColumn('vpn_servers', 'ip_pool')) {
                $table->string('ip_pool')->nullable()->after('ip_range');
            }
            if (!Schema::hasColumn('vpn_servers', 'max_accounts')) {
                $table->integer('max_accounts')->default(100)->after('account_limit');
            }
            if (!Schema::hasColumn('vpn_servers', 'location')) {
                $table->string('location')->nullable()->after('max_accounts');
            }
            if (!Schema::hasColumn('vpn_servers', 'supported_protocols')) {
                $table->json('supported_protocols')->nullable()->after('location');
            }
            if (!Schema::hasColumn('vpn_servers', 'is_online')) {
                $table->boolean('is_online')->default(false)->after('supported_protocols');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vpn_servers', function (Blueprint $table) {
            $table->dropColumn(['profile_name', 'gateway_ip', 'ip_pool', 'max_accounts', 'location', 'supported_protocols', 'is_online']);
        });
    }
};
