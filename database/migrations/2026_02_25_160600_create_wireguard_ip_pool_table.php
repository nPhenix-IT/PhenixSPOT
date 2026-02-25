<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wireguard_ip_pool', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vpn_server_id')->constrained('vpn_servers')->cascadeOnDelete();
            $table->string('ip', 45);
            $table->boolean('is_allocated')->default(false);
            $table->timestamp('allocated_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->unique(['vpn_server_id', 'ip']);
            $table->index(['vpn_server_id', 'is_allocated']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wireguard_ip_pool');
    }
};
