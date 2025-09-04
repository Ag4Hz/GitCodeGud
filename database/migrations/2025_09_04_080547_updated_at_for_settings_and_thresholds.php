<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('level_thresholds', 'updated_at')) {
            Schema::table('level_thresholds', function (Blueprint $table) {
                $table->timestamp('updated_at')->nullable()->after('id');
            });

            DB::table('level_thresholds')->update(['updated_at' => now()]);
        }

        if (!Schema::hasColumn('general_settings', 'updated_at')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->timestamp('updated_at')->nullable()->after('id');
            });

            DB::table('general_settings')->update(['updated_at' => now()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('level_thresholds', 'updated_at')) {
            Schema::table('level_thresholds', function (Blueprint $table) {
                $table->dropColumn('updated_at');
            });
        }

        if (Schema::hasColumn('general_settings', 'updated_at')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->dropColumn('updated_at');
            });
        }
    }
};
