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
        Schema::table('users', function (Blueprint $table) {
            $table->string('oauth_provider_id')->nullable()->after('password');
            $table->string('oauth_provider')->nullable()->after('oauth_provider_id');
            $table->string('oauth_provider_token')->nullable()->after('oauth_provider');
            $table->string('oauth_provider_refresh_token')->nullable()->after('oauth_provider_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('oauth_provider_id');
            $table->dropColumn('oauth_provider');
            $table->dropColumn('oauth_provider_token');
            $table->dropColumn('oauth_provider_refresh_token');
        });
    }
};
