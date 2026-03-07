<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_recharge_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sms_package_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_id')->unique();
            $table->string('payment_method'); // wallet|moneyfusion
            $table->string('status')->default('pending'); // pending|completed|failed
            $table->decimal('amount_fcfa', 12, 2)->default(0);
            $table->unsignedInteger('credits')->default(0);
            $table->string('payment_token')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_recharge_transactions');
    }
};
