<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sms_package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // credit|debit
            $table->string('status')->default('sent'); // sent|failed|blocked|credited
            $table->unsignedInteger('units')->default(1);
            $table->decimal('amount_fcfa', 12, 2)->default(0);
            $table->decimal('balance_after', 12, 2)->default(0);
            $table->string('recipient')->nullable();
            $table->string('sender_name')->nullable();
            $table->text('message')->nullable();
            $table->string('context')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_transactions');
    }
};
