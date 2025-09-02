<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('vpn_servers', function (Blueprint $table) {
            $table->renameColumn('host', 'ip_address');
            $table->integer('api_port')->default(8728)->after('api_password');
            $table->string('domain_name')->nullable()->after('api_port');
            $table->string('ip_range')->nullable()->after('domain_name');
            $table->integer('account_limit')->default(100)->after('ip_range');
        });
    }
    public function down(): void {
        Schema::table('vpn_servers', function (Blueprint $table) {
            $table->renameColumn('ip_address', 'host');
            $table->dropColumn(['api_port', 'domain_name', 'ip_range', 'account_limit']);
        });
    }
};