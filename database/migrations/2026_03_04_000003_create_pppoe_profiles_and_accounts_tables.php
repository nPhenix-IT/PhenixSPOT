<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pppoe_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('rate_limit')->nullable();
            $table->string('local_address')->nullable();
            $table->string('remote_pool')->nullable();
            $table->string('dns_server')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::create('pppoe_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('router_id')->nullable()->constrained('routers')->nullOnDelete();
            $table->foreignId('pppoe_profile_id')->nullable()->constrained('pppoe_profiles')->nullOnDelete();
            $table->string('username');
            $table->string('password');
            $table->string('service_name')->default('pppoe');
            $table->string('ip_address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('status')->default('active');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'username']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pppoe_accounts');
        Schema::dropIfExists('pppoe_profiles');
    }
};
