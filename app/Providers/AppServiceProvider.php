<?php

namespace App\Providers;

use App\Models\Submission;
use App\Observers\SubmissionObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Submission::observe(SubmissionObserver::class);
    }
}
