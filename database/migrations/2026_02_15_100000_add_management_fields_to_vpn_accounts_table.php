<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vpn_accounts', function (Blueprint $table) {
            $table->string('protocol')->nullable()->after('vpn_type');
            $table->string('local_ip')->nullable()->after('local_ip_address');
            $table->string('remote_ip')->nullable()->after('local_ip');
            $table->unsignedInteger('port_api')->nullable()->after('remote_ip');
            $table->unsignedInteger('port_winbox')->nullable()->after('port_api');
            $table->unsignedInteger('port_web')->nullable()->after('port_winbox');
            $table->unsignedInteger('port_custom')->nullable()->after('port_web');
            $table->unsignedInteger('remote_port_api')->nullable()->after('port_custom');
            $table->unsignedInteger('remote_port_winbox')->nullable()->after('remote_port_api');
            $table->unsignedInteger('remote_port_web')->nullable()->after('remote_port_winbox');
            $table->unsignedInteger('remote_port_custom')->nullable()->after('remote_port_web');
            $table->string('commentaire')->nullable()->after('remote_port_custom');
            $table->unsignedTinyInteger('duration_months')->nullable()->after('commentaire');
            $table->timestamp('expires_at')->nullable()->after('duration_months');
            $table->string('status')->default('active')->after('expires_at');
            $table->boolean('is_supplementary')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('vpn_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'protocol',
                'local_ip',
                'remote_ip',
                'port_api',
                'port_winbox',
                'port_web',
                'port_custom',
                'remote_port_api',
                'remote_port_winbox',
                'remote_port_web',
                'remote_port_custom',
                'commentaire',
                'duration_months',
                'expires_at',
                'status',
                'is_supplementary',
            ]);
        });
    }
};
