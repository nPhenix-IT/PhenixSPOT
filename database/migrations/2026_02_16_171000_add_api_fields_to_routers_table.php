<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->string('api_address')->nullable()->after('ip_address');
            $table->unsignedInteger('api_port')->nullable()->after('api_address');
            $table->string('api_user')->nullable()->after('api_port');
            $table->string('api_password')->nullable()->after('api_user');
        });
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn(['api_address', 'api_port', 'api_user', 'api_password']);
        });
    }
};
