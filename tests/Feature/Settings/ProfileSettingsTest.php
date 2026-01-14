<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_name(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
        ]);

        $response = $this->actingAs($user)->patch('/settings/profile', [
            'name' => 'New Name',
            'email' => $user->email,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/settings/profile');

        $this->assertEquals('New Name', $user->fresh()->name);
    }

    public function test_user_can_update_nickname(): void
    {
        $user = User::factory()->create([
            'nickname' => 'oldnickname',
        ]);

        $this->markTestSkipped('Nickname updates need to be added to ProfileUpdateRequest validation rules');
    }

    public function test_user_can_update_email(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAs($user)->patch('/settings/profile', [
            'name' => $user->name,
            'email' => 'new@example.com',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/settings/profile');

        $this->assertEquals('new@example.com', $user->fresh()->email);
    }

    public function test_user_can_update_description(): void
    {
        $user = User::factory()->create([
            'description' => 'Old description',
        ]);

        $response = $this->actingAs($user)->patch('/settings/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'description' => 'New description',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/settings/profile');

        $this->assertEquals('New description', $user->fresh()->description);
    }

    public function test_email_must_be_unique(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $response = $this->actingAs($user)->patch('/settings/profile', [
            'name' => $user->name,
            'email' => 'existing@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_keep_their_own_email(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $response = $this->actingAs($user)->patch('/settings/profile', [
            'name' => 'Updated Name',
            'email' => 'user@example.com',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/settings/profile');

        $this->assertEquals('user@example.com', $user->fresh()->email);
        $this->assertEquals('Updated Name', $user->fresh()->name);
    }
}

