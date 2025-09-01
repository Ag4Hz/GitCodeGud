<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('level_thresholds', function (Blueprint $table) {
            $table->id();
            $table->integer('level')->unique();
            $table->integer('xp_required');
            $table->timestamps();
        });

        // Insert default thresholds
        $defaultThresholds = [
            1 => 0,
            2 => 1000,
            3 => 5000,
            4 => 15000,
            5 => 30000,
            6 => 60000,
            7 => 120000,
            8 => 250000,
            9 => 400000,
            10 => 500000,
        ];

        foreach ($defaultThresholds as $level => $xp) {
            DB::table('level_thresholds')->insert([
                'level' => $level,
                'xp_required' => $xp,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('level_thresholds');
    }
};
