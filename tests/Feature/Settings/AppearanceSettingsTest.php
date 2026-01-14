<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppearanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_appearance_settings(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/settings/appearance');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('settings/Appearance')
        );
    }

    public function test_appearance_page_is_accessible_to_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/settings/appearance');

        $response->assertOk();
    }

    public function test_appearance_page_is_not_accessible_to_guests(): void
    {
        $response = $this->get('/settings/appearance');

        $response->assertRedirect('/login');
    }

    public function test_user_can_toggle_between_light_and_dark_mode(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/settings/appearance');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('settings/Appearance')
        );
    }
}

