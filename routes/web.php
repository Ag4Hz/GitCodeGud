<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BountyController;
use App\Http\Controllers\FollowerController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationInviteController;
use App\Http\Controllers\OrganizationLeaderboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\SubmissionStatusController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

Route::get('/', [DashboardController::class, 'index'])->name('home');
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');
Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/users/{user}', [ProfileController::class, 'show'])->name('users.show');

    Route::post('/users/{user}/follow', [FollowerController::class, 'store'])->name('users.follow');
    Route::delete('/users/{user}/follow', [FollowerController::class, 'destroy'])->name('users.unfollow');

    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/profile/sync-github-skills', [SkillController::class, 'syncGitHub'])
        ->name('profile.sync-github-skills');

    Route::post('/profile/sync-gitlab-skills', [SkillController::class, 'syncGitLab'])
        ->name('profile.sync-gitlab-skills');

    Route::post('/profile/sync-bitbucket-skills', [SkillController::class, 'syncBitbucket'])
        ->name('profile.sync-bitbucket-skills');
});


Route::middleware('auth')->group(function () {
    Route::get('/bounties/{bounty}/submit', [SubmissionController::class, 'create'])->name('submissions.create');
    Route::post('/submissions', [SubmissionController::class, 'store'])->name('submissions.store');
    Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
    Route::get('/my-submissions', [SubmissionController::class, 'userSubmissions'])->name('submissions.mine');
    Route::get('/bounties/{bounty}/submissions', [BountyController::class, 'submissions'])->name('bounties.submissions');
    Route::patch('/submissions/{submission}/status', [SubmissionStatusController::class, 'update'])->name('submissions.status.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/bounties/create', [BountyController::class, 'create'])->name('bounties.create');
    Route::resource('bounties', BountyController::class)->except(['create']);
    Route::patch('/bounties/{id}/restore', [BountyController::class, 'restore'])
        ->where('id', '[0-9]+')
        ->name('bounties.restore');

    Route::get('/bounty/search-repositories', [BountyController::class, 'searchRepositories'])
        ->name('bounty.search-repositories');

    Route::get('/bounty/repositories/{owner}/{repo}/issues', [BountyController::class, 'getRepositoryIssues'])
        ->where('repo', '.*')
        ->name('bounty.repository-issues');

    Route::post('/organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
    Route::patch('/organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
    Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');
    Route::get('/organizations/{organization}/members', [OrganizationController::class, 'members'])->name('organizations.members');

    Route::post('/organizations/{organization}/invite', [OrganizationInviteController::class, 'store'])->name('organizations.invite.store');
    Route::get('/organizations/{organization}/invite/accept', [OrganizationInviteController::class, 'accept'])->name('organizations.invite.accept');
    Route::get('/organizations/{organization}/invite/decline', [OrganizationInviteController::class, 'decline'])->name('organizations.invite.decline');
    Route::get('/organizations/{organization}/leaderboard', [OrganizationLeaderboardController::class, 'index'])->name('organizations.leaderboard');
});

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    Route::post('/admin/thresholds', [AdminController::class, 'updateThresholds'])->name('admin.thresholds.update');
    Route::post('/admin/skill-weights', [AdminController::class, 'updateSkillWeights'])->name('admin.skill-weights.update');
    Route::post('/admin/xp-settings', [AdminController::class, 'updateXPSettings'])->name('admin.xp-settings.update');
    Route::post('/admin/settings/batch-update', [AdminController::class, 'updateAllSettings'])->name('admin.settings.batch-update');
    Route::post('/admin/xp-settings/recalculate', [AdminController::class, 'recalculateXp'])->name('admin.xp-settings.recalculate');

    Route::get('/admin/xp-events', [AdminController::class, 'xpEvents'])->name('admin.xp-events');
    Route::get('/admin/xp-events/export', [AdminController::class, 'exportXpEvents'])->name('admin.xp-events.export');
});

Route::get('/api/xp-settings/last-update', function () {
    $general = DB::table('general_settings')->max('updated_at');
    $thresholds = DB::table('level_thresholds')->max('updated_at');
    $skills = DB::table('user_skills')->max('updated_at');

    $latest = collect([$general, $thresholds, $skills])->filter()->max();

    return response()->json(['updated_at' => $latest]);
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
