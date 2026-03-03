<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('sale_page_settings', function (Blueprint $table) {
      // ✅ Ajout pricing
      if (!Schema::hasColumn('sale_page_settings', 'login_pricing')) {
        $table->json('login_pricing')->nullable()->after('login_contact_label_2');
      }

      // ✅ Suppression phones
      if (Schema::hasColumn('sale_page_settings', 'login_contact_phone_1')) {
        $table->dropColumn('login_contact_phone_1');
      }
      if (Schema::hasColumn('sale_page_settings', 'login_contact_phone_2')) {
        $table->dropColumn('login_contact_phone_2');
      }
    });
  }

  public function down(): void
  {
    Schema::table('sale_page_settings', function (Blueprint $table) {
      if (!Schema::hasColumn('sale_page_settings', 'login_contact_phone_1')) {
        $table->string('login_contact_phone_1', 50)->nullable()->after('login_dns');
      }
      if (!Schema::hasColumn('sale_page_settings', 'login_contact_phone_2')) {
        $table->string('login_contact_phone_2', 50)->nullable()->after('login_contact_label_1');
      }

      if (Schema::hasColumn('sale_page_settings', 'login_pricing')) {
        $table->dropColumn('login_pricing');
      }
    });
  }
};