<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_providers', function (Blueprint $table) {
            $table->text('token')->nullable()->change();
            $table->text('refresh_token')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_providers', function (Blueprint $table) {
            $table->string('token')->nullable()->change();
            $table->string('refresh_token')->nullable()->change();
        });
    }
};
