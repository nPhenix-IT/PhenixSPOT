<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('vpn_servers', function (Blueprint $table) {
            $table->string('local_ip_address')->nullable()->after('domain_name');
        });
    }
    public function down(): void {
        Schema::table('vpn_servers', function (Blueprint $table) {
            $table->dropColumn('local_ip_address');
        });
    }
};