<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pppoe_profiles') && !Schema::hasColumn('pppoe_profiles', 'pool_exclusions')) {
            Schema::table('pppoe_profiles', function (Blueprint $table) {
                $table->text('pool_exclusions')->nullable()->after('remote_pool');
            });
        }

        Schema::create('pppoe_ip_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pppoe_profile_id')->constrained('pppoe_profiles')->cascadeOnDelete();
            $table->foreignId('pppoe_account_id')->nullable()->constrained('pppoe_accounts')->nullOnDelete();
            $table->string('ip_address');
            $table->string('status', 20)->default('reserved'); // reserved|allocated|excluded
            $table->string('note')->nullable();
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['pppoe_profile_id', 'ip_address']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('pppoe_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pppoe_account_id')->nullable()->constrained('pppoe_accounts')->nullOnDelete();
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('status', 20)->default('ok');
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('provisioned_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'action']);
            $table->index(['pppoe_account_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pppoe_audit_logs');
        Schema::dropIfExists('pppoe_ip_reservations');

        if (Schema::hasTable('pppoe_profiles') && Schema::hasColumn('pppoe_profiles', 'pool_exclusions')) {
            Schema::table('pppoe_profiles', function (Blueprint $table) {
                $table->dropColumn('pool_exclusions');
            });
        }
    }
};
