<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('limit_type')->default('unlimited')->after('price');
            $table->renameColumn('shared_users', 'device_limit');
            $table->renameColumn('validity', 'validity_period');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('limit_type');
            $table->renameColumn('device_limit', 'shared_users');
            $table->renameColumn('validity_period', 'validity');
        });
    }
};
