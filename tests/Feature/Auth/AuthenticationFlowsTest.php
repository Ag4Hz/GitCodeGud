<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;

it('renders the login page', function () {
    $this->get('/login')->assertOk();
});

it('renders the registration page', function () {
    $this->get('/register')->assertOk();
});

it('renders the password reset request page', function () {
    $this->get('/forgot-password')->assertOk();
});

it('user can register with email and password', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $response->assertRedirect(route('register.linking', absolute: false));

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);
});

it('duplicate email registration is prevented', function () {
    User::factory()->create(['email' => 'dupe@example.com']);

    $response = $this->from('/register')->post('/register', [
        'name' => 'Another User',
        'email' => 'dupe@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
    $response->assertRedirect('/register');
    $response->assertSessionHasErrors('email');
});

it('registration validates required fields (name, email, password)', function () {
    $response = $this->from('/register')->post('/register', []);

    $this->assertGuest();
    $response->assertRedirect('/register');

    $response->assertSessionHasErrors(['name', 'email', 'password']);
});

it('user can logout successfully', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

it('user can request password reset', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email])
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($user, ResetPassword::class);
});

it('password reset page renders correctly', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $this->get('/reset-password/' . $notification->token)->assertOk();
        return true;
    });
});

