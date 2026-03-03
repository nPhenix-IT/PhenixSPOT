<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('onsite_sale_wallet', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();

            // Optionnel: utile si tu veux tracer le routeur qui a activé le voucher
            $table->foreignId('router_id')->nullable()->constrained('routers')->nullOnDelete();

            // FCFA -> entier, pas de décimales
            $table->unsignedBigInteger('amount');

            // Pour rester cohérent avec ta table transactions
            $table->string('type')->default('credit'); // credit / debit si un jour tu ajoutes des corrections

            $table->string('description')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['voucher_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onsite_sale_wallet');
    }
};