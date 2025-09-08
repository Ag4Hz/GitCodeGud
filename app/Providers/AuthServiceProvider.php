<?php

namespace App\Providers;

use App\Models\Submission;
use App\Models\User;
use App\Policies\SubmissionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Submission::class => SubmissionPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
