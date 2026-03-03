<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sale_page_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_page_settings', 'login_pricing')) {
                $table->json('login_pricing')->nullable()->after('login_contact_label_2');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_page_settings', function (Blueprint $table) {
            if (Schema::hasColumn('sale_page_settings', 'login_pricing')) {
                $table->dropColumn('login_pricing');
            }
        });
    }
};