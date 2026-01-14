<?php

namespace Tests\Feature\Admin;

use App\Helpers\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_users_can_access_admin_panel(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
    }

    public function test_non_admin_users_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::USER->value,
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertForbidden();
    }

    public function test_guest_users_cannot_access_admin_panel(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }
}



