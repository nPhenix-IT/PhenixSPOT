<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('user_payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('provider_name'); // Ex: 'cinetpay', 'paypal'
            $table->text('api_key')->nullable(); // Stockage chiffré
            $table->text('secret_key')->nullable(); // Stockage chiffré
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('user_payment_gateways'); }
};