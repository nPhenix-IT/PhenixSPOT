<?php
//php artisan migrate --path=/database/migrations/2026_03_03_145518_alter_commission_payer_in_sale_page_settings_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('sale_page_settings', function (Blueprint $table) {
      // ✅ On passe en string pour éviter les limites d'enum
      $table->string('commission_payer', 20)->default('seller')->change();
    });
  }

  public function down(): void
  {
    Schema::table('sale_page_settings', function (Blueprint $table) {
      // ⚠️ Si avant c'était enum, on ne peut pas revenir proprement sans connaître la définition exacte.
      // On remet en string par sécurité.
      $table->string('commission_payer', 20)->default('seller')->change();
    });
  }
};