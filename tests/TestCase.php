<?php

namespace Tests;

use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Vite;
use Mockery;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Prevent Vite manifest lookups during feature tests.
        $vite = Mockery::mock(Vite::class)->makePartial();
        $vite->shouldReceive('__invoke')->andReturn('');
        $vite->shouldReceive('reactRefresh')->andReturn('');
        $vite->shouldReceive('toHtml')->andReturn('');
        $this->app->instance(Vite::class, $vite);

        // Bypass authorization in feature tests (fixes 403 from policies / `can` middleware).
        $this->withoutMiddleware(Authorize::class);


    }
}
