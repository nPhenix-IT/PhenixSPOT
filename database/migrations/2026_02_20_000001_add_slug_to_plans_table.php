<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('plans', 'slug')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->string('slug')->nullable()->unique()->after('name');
            });
        }

        DB::table('plans')->select('id', 'name', 'slug')->orderBy('id')->get()->each(function ($plan) {
            if (!empty($plan->slug)) {
                return;
            }

            $baseSlug = Str::slug($plan->name ?: 'plan');
            $slug = $baseSlug;
            $suffix = 1;

            while (DB::table('plans')->where('slug', $slug)->where('id', '!=', $plan->id)->exists()) {
                $slug = $baseSlug . '-' . $suffix;
                $suffix++;
            }

            DB::table('plans')->where('id', $plan->id)->update(['slug' => $slug]);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('plans', 'slug')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->dropUnique(['slug']);
                $table->dropColumn('slug');
            });
        }
    }
};
