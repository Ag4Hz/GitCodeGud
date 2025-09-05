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
        if (DB::DriverName() !== 'sqlite') {
            DB::statement('DROP PROCEDURE IF EXISTS recalculate_user_xp;');

            DB::unprepared(<<<'SQL'
            CREATE PROCEDURE recalculate_user_xp()
            LANGUAGE plpgsql
            AS $PROC$
            DECLARE
                base_xp_value INTEGER;
                bonus_multiplier_value NUMERIC(8,2);
            BEGIN
                -- Get current XP settings from general_settings table
                SELECT
                    COALESCE((SELECT value::INTEGER FROM general_settings WHERE key = 'base_xp' LIMIT 1), 100),
                    COALESCE((SELECT value::NUMERIC FROM general_settings WHERE key = 'bonus_multiplier' LIMIT 1), 1.5)
                INTO base_xp_value, bonus_multiplier_value;

                -- Update individual skill XP
                UPDATE user_skills us
                SET xp = base_xp_value * bonus_multiplier_value * s.multiplier
                FROM skills s
                WHERE us.skill_id = s.id;

                -- Recalculate total XP per user
                UPDATE users u
                SET xp = COALESCE(st.total_xp, 0)
                FROM (
                    SELECT user_id, SUM(xp) AS total_xp
                    FROM user_skills
                    GROUP BY user_id
                ) st
                WHERE u.id = st.user_id;

                -- Users with no skills -> 0
                UPDATE users
                SET xp = 0
                WHERE id NOT IN (SELECT DISTINCT user_id FROM user_skills);

                RAISE NOTICE 'XP recalculation completed. Base XP: %, Bonus Multiplier: %',
                    base_xp_value, bonus_multiplier_value;
            END;
            $PROC$;
            SQL
                );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::DriverName() !== 'sqlite') {
            DB::statement('DROP PROCEDURE IF EXISTS recalculate_user_xp;');
        }
    }
};
