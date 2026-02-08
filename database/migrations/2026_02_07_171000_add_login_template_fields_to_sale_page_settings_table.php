<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_page_settings', function (Blueprint $table) {
            $table->string('login_primary_color')->nullable()->after('primary_color');
            $table->text('login_ticker_text')->nullable()->after('login_primary_color');
            $table->string('login_contact_phone_1')->nullable()->after('login_ticker_text');
            $table->string('login_contact_label_1')->nullable()->after('login_contact_phone_1');
            $table->string('login_contact_phone_2')->nullable()->after('login_contact_label_1');
            $table->string('login_contact_label_2')->nullable()->after('login_contact_phone_2');
        });
    }

    public function down(): void
    {
        Schema::table('sale_page_settings', function (Blueprint $table) {
            $table->dropColumn([
                'login_primary_color',
                'login_ticker_text',
                'login_contact_phone_1',
                'login_contact_label_1',
                'login_contact_phone_2',
                'login_contact_label_2',
            ]);
        });
    }
};
