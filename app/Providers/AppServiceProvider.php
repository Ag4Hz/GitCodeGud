<?php

namespace App\Providers;

use App\Models\Submission;
use App\Models\User;
use App\Policies\SubmissionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Submission::class => SubmissionPolicy::class,
        ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
