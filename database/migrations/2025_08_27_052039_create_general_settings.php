<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->string('type')->default('integer'); // integer, float, string
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default values
        DB::table('xp_settings')->insert([
            [
                'key' => 'base_xp',
                'value' => '100',
                'type' => 'integer',
                'description' => 'Base XP awarded for completing tasks',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'bonus_multiplier',
                'value' => '1.5',
                'type' => 'float',
                'description' => 'Multiplier applied to bonus XP',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('xp_settings');
    }
};
