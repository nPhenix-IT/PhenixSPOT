<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vpn_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('vpn_user')->unique();
            $table->string('vpn_password');
            $table->string('protocol'); // Ex: 'l2tp', 'openvpn', 'sstp'
            $table->string('profile_name')->default('default'); // Profil PPP sur le MikroTik
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('vpn_accounts'); }
};
