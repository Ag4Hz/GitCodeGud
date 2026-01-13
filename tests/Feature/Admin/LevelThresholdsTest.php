<?php

namespace Tests\Feature\Admin;

use App\Helpers\UserRole;
use App\Models\LevelThreshold;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LevelThresholdsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        LevelThreshold::query()->delete();
    }

    public function test_level_thresholds_page_displays_all_levels(): void
    {
        LevelThreshold::create(['level' => 1, 'xp_required' => 100]);
        LevelThreshold::create(['level' => 2, 'xp_required' => 250]);
        LevelThreshold::create(['level' => 3, 'xp_required' => 500]);

        $response = $this->actingAs($this->admin)->get('/admin');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin')
            ->has('xpConfig')
        );
    }

    public function test_each_level_shows_current_xp_threshold(): void
    {
        LevelThreshold::create(['level' => 1, 'xp_required' => 100]);
        LevelThreshold::create(['level' => 2, 'xp_required' => 250]);

        $thresholds = LevelThreshold::getThresholds();

        $this->assertEquals(100, $thresholds[1]);
        $this->assertEquals(250, $thresholds[2]);
    }

    public function test_threshold_validation_requires_increasing_values(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/thresholds', [
            'thresholds' => [100, 90, 300],
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_threshold_validation_requires_values_greater_than_zero(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/thresholds', [
            'thresholds' => [0, 100, 200],
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_threshold_validation_requires_numeric_values(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/thresholds', [
            'thresholds' => ['abc', 100, 200],
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_updated_thresholds_saved_to_database(): void
    {
        $newThresholds = [100, 250, 500, 1000];

        $response = $this->actingAs($this->admin)->post('/admin/thresholds', [
            'thresholds' => $newThresholds,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $savedThresholds = LevelThreshold::orderBy('level')->pluck('xp_required')->toArray();

        $this->assertEquals($newThresholds, $savedThresholds);
    }

    public function test_user_levels_recalculate_after_threshold_changes(): void
    {
        // Create initial thresholds
        LevelThreshold::create(['level' => 1, 'xp_required' => 100]);
        LevelThreshold::create(['level' => 2, 'xp_required' => 200]);
        LevelThreshold::create(['level' => 3, 'xp_required' => 300]);

        $user1 = User::factory()->create(['xp' => 150]);
        $user2 = User::factory()->create(['xp' => 250]);

        $response = $this->actingAs($this->admin)->post('/admin/thresholds', [
            'thresholds' => [50, 150, 300],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $savedThresholds = LevelThreshold::orderBy('level')->pluck('xp_required')->toArray();
        $this->assertEquals([50, 150, 300], $savedThresholds);
    }

    public function test_admin_can_update_level_thresholds(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/thresholds', [
            'thresholds' => [100, 250, 500],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseCount('level_thresholds', 3);
    }

    public function test_non_admin_cannot_update_level_thresholds(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::USER->value,
        ]);

        $response = $this->actingAs($user)->post('/admin/thresholds', [
            'thresholds' => [100, 250, 500],
        ]);

        $response->assertForbidden();
    }
}

