<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('radius_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('ip_address');
            $table->text('radius_secret'); // Sera chiffré
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('radius_servers'); }
};