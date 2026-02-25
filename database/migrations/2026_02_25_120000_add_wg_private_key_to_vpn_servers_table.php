<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vpn_servers', function (Blueprint $table) {
            if (!Schema::hasColumn('vpn_servers', 'wg_server_private_key')) {
                $table->string('wg_server_private_key', 255)
                    ->nullable()
                    ->after('wg_server_public_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vpn_servers', function (Blueprint $table) {
            if (Schema::hasColumn('vpn_servers', 'wg_server_private_key')) {
                $table->dropColumn('wg_server_private_key');
            }
        });
    }
};
