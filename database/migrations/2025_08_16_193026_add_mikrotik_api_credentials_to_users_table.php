<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mikrotik_host')->nullable()->after('remember_token');
            $table->string('mikrotik_user')->nullable()->after('mikrotik_host');
            $table->text('mikrotik_password')->nullable()->after('mikrotik_user'); // Chiffré
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mikrotik_host', 'mikrotik_user', 'mikrotik_password']);
        });
    }
};