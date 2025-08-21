<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use App\Http\Controllers\DashboardController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/users/search', [DashboardController::class, 'searchUsers'])
        ->name('users.search');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
});

Route::middleware('auth')->group(function () {
    Route::post('/profile/sync-github-skills', [ProfileController::class, 'syncGitHubSkills'])
        ->name('profile.sync-github-skills');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
