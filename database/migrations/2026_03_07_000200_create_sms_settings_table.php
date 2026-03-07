<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('unit_cost_fcfa', 10, 2)->default(25);
            $table->string('default_sender_name')->default('PhenixSPOT');
            $table->timestamps();
        });

        DB::table('sms_settings')->insert([
            'unit_cost_fcfa' => 25,
            'default_sender_name' => 'PhenixSPOT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_settings');
    }
};
