<?php

use App\Http\Controllers\Socialite\ProviderCallbackController;
use App\Http\Controllers\Socialite\ProviderRedirectController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/auth/github/redirect' ,  ProviderRedirectController::class)->name('github.redirect');
Route::get('/auth/github/callback' ,  ProviderCallbackController::class)->name('github.callback');


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
