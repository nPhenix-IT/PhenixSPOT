<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('vpn_servers', 'wg_server_private_key')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE vpn_servers MODIFY wg_server_private_key TEXT NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE vpn_servers ALTER COLUMN wg_server_private_key TYPE TEXT');
            return;
        }

        if ($driver === 'sqlite') {
            // SQLite n'impose pas strictement la longueur VARCHAR comme MySQL.
            return;
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('vpn_servers', 'wg_server_private_key')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE vpn_servers MODIFY wg_server_private_key VARCHAR(255) NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE vpn_servers ALTER COLUMN wg_server_private_key TYPE VARCHAR(255)');
            return;
        }
    }
};
