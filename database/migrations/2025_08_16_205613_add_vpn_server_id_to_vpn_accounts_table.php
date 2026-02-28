<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('vpn_accounts', function (Blueprint $table) {
            $table->foreignId('vpn_server_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
        });
    }
    public function down(): void {
        Schema::table('vpn_accounts', function (Blueprint $table) {
            $table->dropForeign(['vpn_server_id']);
            $table->dropColumn('vpn_server_id');
        });
    }
};