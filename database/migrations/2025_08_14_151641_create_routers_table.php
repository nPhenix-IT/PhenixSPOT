<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Nom donné par l'utilisateur (Ex: "Hotspot Principal")
            $table->string('ip_address')->unique(); // Adresse IP publique du routeur
            $table->string('radius_secret'); // Secret partagé pour la communication RADIUS
            $table->string('model')->nullable(); // Ex: "MikroTik RB750Gr3"
            $table->string('location')->nullable(); // Ex: "Abidjan, Cocody"
            $table->enum('status', ['active', 'inactive', 'pending'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routers');
    }
};