<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BountyController;
use App\Http\Controllers\FollowerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GitHubSkillController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/users/{user}', [ProfileController::class, 'show'])->name('users.show');

    Route::post('/users/{user}/follow', [FollowerController::class, 'store'])->name('users.follow');
    Route::delete('/users/{user}/follow', [FollowerController::class, 'destroy'])->name('users.unfollow');
});

Route::middleware('auth')->group(function () {
    Route::post('/profile/sync-github-skills', [GitHubSkillController::class, 'sync'])
    Route::post('/profile/sync-github-skills', [ProfileController::class, 'handleGitHubSkillsSync'])
        ->name('profile.sync-github-skills');
});

Route::middleware(['auth'])->group(function () {
    Route::resource('bounties', BountyController::class)->except(['create']);

    Route::patch('/bounties/{id}/restore', [BountyController::class, 'restore'])
        ->where('id', '[0-9]+')
        ->name('bounties.restore');

    Route::get('/api/bounties/popular', [BountyController::class, 'popular'])
        ->name('bounties.popular');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    Route::post('/admin/thresholds', [AdminController::class, 'updateThresholds'])->name('admin.thresholds.update');
    Route::post('/admin/skill-weights', [AdminController::class, 'updateSkillWeights'])->name('admin.skill-weights.update');
    Route::post('/admin/xp-settings', [AdminController::class, 'updateXPSettings'])->name('admin.xp-settings.update');
    Route::post('/admin/settings/batch-update', [AdminController::class, 'updateAllSettings'])->name('admin.settings.batch-update');
});

Route::middleware('auth')->group(function () {
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
