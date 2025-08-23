<?php

use App\Http\Controllers\BountyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LeaderboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/{user}', [ProfileController::class, 'show'])->whereNumber('user');
});

Route::middleware('auth')->group(function () {
    Route::post('/profile/sync-github-skills', [ProfileController::class, 'syncGitHubSkills'])
        ->name('profile.sync-github-skills');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::post('/bounties', [BountyController::class, 'store'])->name('bounties.store');
    Route::get('/bounties/{bounty}', [BountyController::class, 'show'])->name('bounties.show');
    Route::get('/bounties/{bounty}/edit', [BountyController::class, 'edit'])->name('bounties.edit');
    Route::patch('/bounties/{bounty}', [BountyController::class, 'update'])->name('bounties.update');

    Route::delete('/bounties/{id}', [BountyController::class, 'destroy'])
        ->where('id', '[0-9]+')
        ->name('bounties.destroy');
    Route::patch('/bounties/{id}/restore', [BountyController::class, 'restore'])
        ->where('id', '[0-9]+')
        ->name('bounties.restore');

    Route::get('/bounties', [BountyController::class, 'index'])->name('bounties.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])
        ->name('leaderboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
