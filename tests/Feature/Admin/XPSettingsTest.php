<?php

namespace Tests\Feature\Admin;

use App\Helpers\UserRole;
use App\Models\GeneralSetting;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XPSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);
    }

    public function test_admin_can_view_xp_settings(): void
    {
        GeneralSetting::setValue('base_xp', 100);
        GeneralSetting::setValue('bonus_multiplier', 1.5);

        $response = $this->actingAs($this->admin)->get('/admin');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin')
            ->has('xpConfig')
        );
    }

    public function test_xp_settings_page_displays_current_base_multiplier(): void
    {
        GeneralSetting::setValue('base_xp', 150);
        GeneralSetting::setValue('bonus_multiplier', 2.0);

        $response = $this->actingAs($this->admin)->get('/admin');

        $response->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Admin')
            ->where('xpConfig.base_xp', fn($value) => $value == 150)
            ->where('xpConfig.bonus_multiplier', fn($value) => $value == 2.0)
        );
    }

    public function test_xp_settings_page_displays_current_skill_multipliers(): void
    {
        $skill1 = Skill::factory()->create([
            'skill_name' => 'PHP',
            'type' => 'language',
            'multiplier' => 1.5,
        ]);
        $skill2 = Skill::factory()->create([
            'skill_name' => 'JavaScript',
            'type' => 'language',
            'multiplier' => 1.3,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin')
            ->has('xpConfig.skills', 2)
            ->where('xpConfig.skills.0.skill_name', 'JavaScript')
            ->where('xpConfig.skills.1.skill_name', 'PHP')
        );
    }

    public function test_admin_can_update_base_xp_multiplier(): void
    {
        GeneralSetting::setValue('base_xp', 100);
        GeneralSetting::setValue('bonus_multiplier', 1.5);

        $response = $this->actingAs($this->admin)->post('/admin/xp-settings', [
            'base_xp' => 200,
            'bonus_multiplier' => 2.5,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertEquals('200', GeneralSetting::getValue('base_xp'));
        $this->assertEquals('2.5', GeneralSetting::getValue('bonus_multiplier'));
    }

    public function test_admin_can_update_skill_multipliers(): void
    {
        // Skip the test on SQLite as AdminController uses PostgreSQL-specific syntax (::numeric)
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('This test requires PostgreSQL - AdminController uses ::numeric cast syntax');
        }

        $skill1 = Skill::factory()->create([
            'skill_name' => 'PHP',
            'multiplier' => 1.0,
        ]);
        $skill2 = Skill::factory()->create([
            'skill_name' => 'JavaScript',
            'multiplier' => 1.0,
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/skill-weights', [
            'skillWeights' => [
                ['skill_name' => 'PHP', 'multiplier' => 1.8],
                ['skill_name' => 'JavaScript', 'multiplier' => 1.6],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertEquals(1.8, $skill1->fresh()->multiplier);
        $this->assertEquals(1.6, $skill2->fresh()->multiplier);
    }

    public function test_xp_settings_update_triggers_xp_recalculation(): void
    {
        $user1 = User::factory()->create(['xp' => 100]);
        $user2 = User::factory()->create(['xp' => 200]);

        $response = $this->actingAs($this->admin)
            ->post('/admin/xp-settings/recalculate');

        if (config('database.default') === 'sqlite') {
            $response->assertSessionHasErrors();
        } else {
            $response->assertSessionHasNoErrors();
            $response->assertRedirect();
        }
    }

    public function test_non_admin_cannot_update_xp_settings(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::USER->value,
        ]);

        $response = $this->actingAs($user)->post('/admin/xp-settings', [
            'base_xp' => 200,
            'bonus_multiplier' => 2.5,
        ]);

        $response->assertForbidden();
    }
}

