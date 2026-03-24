<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropUnique(['git_id']);
            $table->string('git_id')->nullable()->change();
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE issues DROP CONSTRAINT IF EXISTS issues_provider_check");
            DB::statement("ALTER TABLE issues ADD CONSTRAINT issues_provider_check CHECK (provider IN ('github', 'gitlab', 'bitbucket', 'jira'))");
        }
        Schema::table('issues', function (Blueprint $table) {
            $table->unique('url');
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropUnique(['url']);
            $table->string('git_id')->nullable(false)->change();
            $table->unique('git_id');
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE issues DROP CONSTRAINT IF EXISTS issues_provider_check");
            DB::statement("ALTER TABLE issues ADD CONSTRAINT issues_provider_check CHECK (provider IN ('github', 'gitlab', 'bitbucket'))");
        }
    }
};
