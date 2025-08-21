<?php

use App\Http\Controllers\BountyController;
use App\Http\Controllers\ProfileController;
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
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
