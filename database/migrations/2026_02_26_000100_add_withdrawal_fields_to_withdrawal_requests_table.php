<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('withdrawal_requests', 'country_code')) {
                $table->string('country_code', 8)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('withdrawal_requests', 'withdraw_mode')) {
                $table->string('withdraw_mode', 120)->nullable()->after('country_code');
            }
            if (!Schema::hasColumn('withdrawal_requests', 'phone_number')) {
                $table->string('phone_number', 30)->nullable()->after('withdraw_mode');
            }
        });

        DB::table('withdrawal_requests')
            ->select('id', 'user_id', 'payment_details')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $details = json_decode($row->payment_details ?? '{}', true) ?: [];

                    $country = strtolower((string) ($details['country_code'] ?? ''));
                    if ($country === '') {
                        $userCountry = DB::table('users')->where('id', $row->user_id)->value('country_code');
                        $country = strtolower((string) ($userCountry ?: 'ci'));
                    }

                    $mode = (string) ($details['withdraw_mode'] ?? $details['method'] ?? '');
                    $phone = (string) ($details['phone'] ?? '');

                    DB::table('withdrawal_requests')
                        ->where('id', $row->id)
                        ->update([
                            'country_code' => $country ?: null,
                            'withdraw_mode' => $mode ?: null,
                            'phone_number' => $phone ?: null,
                        ]);
                }
            }, 'id');
    }

    public function down(): void
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            if (Schema::hasColumn('withdrawal_requests', 'phone_number')) {
                $table->dropColumn('phone_number');
            }
            if (Schema::hasColumn('withdrawal_requests', 'withdraw_mode')) {
                $table->dropColumn('withdraw_mode');
            }
            if (Schema::hasColumn('withdrawal_requests', 'country_code')) {
                $table->dropColumn('country_code');
            }
        });
    }
};
