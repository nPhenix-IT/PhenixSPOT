<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Ex: "Forfait 1 Jour"
            $table->decimal('price', 8, 2)->nullable(); // Prix de vente du voucher
            $table->integer('session_timeout')->nullable(); // Durée totale de connexion (en secondes)
            $table->string('rate_limit')->nullable(); // Vitesse (ex: 512k/1M)
            $table->bigInteger('data_limit')->nullable(); // Limite de données en octets (ex: 1073741824 pour 1 Go)
            $table->integer('validity')->nullable(); // Durée de validité du voucher en secondes (ex: 86400 pour 1 jour)
            $table->integer('shared_users')->default(1); // Nombre d'utilisateurs simultanés
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
