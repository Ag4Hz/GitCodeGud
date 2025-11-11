<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Move existing OAuth data into a new table with 100 chunks
        DB::table('users')
            ->whereNotNull('oauth_provider')
            ->orderBy('id')
            ->chunk(100, function($users) {
            foreach ($users as $user) {
                DB::table('user_providers')->insert([
                    'user_id' => $user->id,
                    'provider' => $user->oauth_provider,
                    'provider_id' => $user->oauth_provider_id ?? null,
                    'provider_email' => $user->email,
                    'token' => $user->oauth_provider_token ?? null,
                    'refresh_token' => $user->oauth_provider_refresh_token ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback inserted data
        DB::table('user_providers')->delete();
    }
};
