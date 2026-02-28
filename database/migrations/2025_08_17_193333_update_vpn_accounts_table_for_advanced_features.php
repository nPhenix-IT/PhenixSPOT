<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('vpn_accounts', function (Blueprint $table) {
            $table->renameColumn('vpn_user', 'username');
            $table->renameColumn('vpn_password', 'password');
            $table->renameColumn('protocol', 'vpn_type');
            $table->string('server_address')->nullable()->after('vpn_type');
            $table->string('local_ip_address')->nullable()->after('server_address');
            $table->boolean('forward_api')->default(false);
            $table->boolean('forward_winbox')->default(false);
            $table->boolean('forward_web')->default(false);
        });
    }
    public function down(): void { /* Pour la simplicité, nous omettons la logique de rollback */ }
};