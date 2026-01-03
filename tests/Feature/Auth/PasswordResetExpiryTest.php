<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

it('expired reset token is rejected', function () {
    $user = User::factory()->create(['email' => 'reset-expired@example.com']);

    $rawToken = Str::random(64);

    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make($rawToken),
        'created_at' => Carbon::now()->subHours(48),
    ]);

    $response = $this->from('/reset-password/' . $rawToken)->post('/reset-password', [
        'token' => $rawToken,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertRedirect('/reset-password/' . $rawToken);
    $response->assertSessionHasErrors('email');
});

