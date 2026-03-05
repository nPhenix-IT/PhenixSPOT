<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('pppoe_profiles')) {
            return;
        }

        Schema::table('pppoe_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('pppoe_profiles', 'price')) {
                $table->decimal('price', 10, 2)->default(0)->after('name');
            }
            if (!Schema::hasColumn('pppoe_profiles', 'limit_type')) {
                $table->string('limit_type', 20)->default('unlimited')->after('price');
            }
            if (!Schema::hasColumn('pppoe_profiles', 'session_timeout')) {
                $table->unsignedBigInteger('session_timeout')->default(0)->after('rate_limit');
            }
            if (!Schema::hasColumn('pppoe_profiles', 'data_limit')) {
                $table->unsignedBigInteger('data_limit')->default(0)->after('session_timeout');
            }
            if (!Schema::hasColumn('pppoe_profiles', 'validity_period')) {
                $table->unsignedBigInteger('validity_period')->default(0)->after('data_limit');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pppoe_profiles')) {
            return;
        }

        Schema::table('pppoe_profiles', function (Blueprint $table) {
            $columns = [];
            foreach (['price', 'limit_type', 'session_timeout', 'data_limit', 'validity_period'] as $column) {
                if (Schema::hasColumn('pppoe_profiles', $column)) {
                    $columns[] = $column;
                }
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};

