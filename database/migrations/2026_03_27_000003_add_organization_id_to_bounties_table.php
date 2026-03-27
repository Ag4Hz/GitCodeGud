<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bounties', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('issue_id')
                ->constrained('organizations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bounties', function (Blueprint $table) {
            if (Schema::hasColumn('bounties', 'organization_id')) {
                $table->dropForeignIdFor(\App\Models\Organization::class);
                $table->dropColumn('organization_id');
            }
        });
    }
};
