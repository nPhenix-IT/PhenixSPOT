<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('coupons')) {
            Schema::table('coupons', function (Blueprint $table) {
                if (!Schema::hasColumn('coupons', 'starts_at')) {
                    $table->timestamp('starts_at')->nullable()->after('value');
                }
                if (!Schema::hasColumn('coupons', 'ends_at')) {
                    $table->timestamp('ends_at')->nullable()->after('starts_at');
                }
                if (!Schema::hasColumn('coupons', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->after('ends_at')->constrained()->nullOnDelete();
                }
                if (!Schema::hasColumn('coupons', 'plan_id')) {
                    $table->foreignId('plan_id')->nullable()->after('user_id')->constrained('plans')->nullOnDelete();
                }
            });
        }

        if (!Schema::hasTable('coupon_usages')) {
            Schema::create('coupon_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
                $table->string('transaction_id')->nullable();
                $table->timestamp('used_at')->nullable();
                $table->timestamps();

                $table->unique(['coupon_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');

        if (Schema::hasTable('coupons')) {
            Schema::table('coupons', function (Blueprint $table) {
                $columns = [];
                foreach (['starts_at', 'ends_at', 'user_id', 'plan_id'] as $column) {
                    if (Schema::hasColumn('coupons', $column)) {
                        $columns[] = $column;
                    }
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};

