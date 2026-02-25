<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wireguard_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vpn_server_id')->constrained('vpn_servers')->cascadeOnDelete();
            $table->foreignId('router_id')->constrained('routers')->cascadeOnDelete();

            $table->string('client_ip', 64);
            $table->text('client_public_key');
            $table->text('client_private_key');
            $table->text('preshared_key')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['vpn_server_id', 'client_ip']);
            $table->unique(['router_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wireguard_clients');
    }
};
