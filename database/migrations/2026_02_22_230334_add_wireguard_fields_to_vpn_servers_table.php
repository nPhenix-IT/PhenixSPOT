<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('vpn_servers', function (Blueprint $table) {
      $table->string('server_type')->nullable()->index(); // routeros | wireguard

      $table->string('wg_network', 32)->nullable();              // 10.99.0.0/24
      $table->string('wg_server_address', 45)->nullable();       // 10.99.0.1
      $table->string('wg_server_public_key', 255)->nullable();   // SERVER_PUBLIC_KEY
      $table->string('wg_endpoint_address', 255)->nullable();    // 13.49.241.181 ou domaine
      $table->unsignedInteger('wg_endpoint_port')->nullable();   // 51820

      // utiles pour générer des clients plus tard
      $table->string('wg_interface', 64)->nullable();            // phenixspot-tunnel
      $table->string('wg_dns', 255)->nullable();                 // 1.1.1.1,8.8.8.8
      $table->unsignedSmallInteger('wg_mtu')->nullable();        // 1420
      $table->unsignedSmallInteger('wg_persistent_keepalive')->nullable(); // 25
      $table->string('wg_client_ip_start', 45)->nullable();      // 10.99.0.2
    });
  }

  public function down(): void
  {
    Schema::table('vpn_servers', function (Blueprint $table) {
      $table->dropColumn([
        'server_type',
        'wg_network',
        'wg_server_address',
        'wg_server_public_key',
        'wg_endpoint_address',
        'wg_endpoint_port',
        'wg_interface',
        'wg_dns',
        'wg_mtu',
        'wg_persistent_keepalive',
        'wg_client_ip_start',
      ]);
    });
  }
};