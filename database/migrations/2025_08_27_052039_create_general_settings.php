<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default values
        DB::table('general_settings')->insert([
            [
                'key' => 'base_xp',
                'value' => '100',
                'description' => 'Base XP awarded for completing tasks',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'bonus_multiplier',
                'value' => '1.5',
                'description' => 'Multiplier applied to bonus XP',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('general_settings');
    }
};
