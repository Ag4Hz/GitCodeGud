<?php

namespace App\Providers;

use App\Models\Submission;
use App\Observers\SubmissionObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Dusk\DuskServiceProvider::class);
        }
    }

    public function boot(): void
    {
        Submission::observe(SubmissionObserver::class);

        Event::listen(SocialiteWasCalled::class, \SocialiteProviders\Atlassian\AtlassianExtendSocialite::class . '@handle');
        //Event::listen(SocialiteWasCalled::class, \SocialiteProviders\Bitbucket\BitbucketExtendSocialite::class . '@handle');

        $socialite = $this->app->make(\Laravel\Socialite\Contracts\Factory::class);
        $socialite->extend('bitbucket', function ($app) use ($socialite) {
            $config = $app['config']['services.bitbucket'];
            return $socialite->buildProvider(\App\Socialite\BitbucketProvider::class, $config);
        });
    }
}
