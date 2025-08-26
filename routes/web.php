<?php

use App\Http\Controllers\BountyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LeaderboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/{user}', [ProfileController::class, 'show'])->whereNumber('user');

    Route::post('/profile/sync-github-skills', [ProfileController::class, 'syncGitHubSkills'])
        ->name('profile.sync-github-skills');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::resource('bounties', BountyController::class)->except(['create']);
    Route::patch('/bounties/{id}/restore', [BountyController::class, 'restore'])
        ->where('id', '[0-9]+')
        ->name('bounties.restore');
});

Route::middleware('auth')->group(function () {
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])
        ->name('leaderboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
