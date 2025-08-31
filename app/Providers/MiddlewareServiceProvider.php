<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MiddlewareServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register middleware aliases
        $router = $this->app['router'];
        $router->aliasMiddleware('employer', \App\Http\Middleware\EmployerMiddleware::class);
        $router->aliasMiddleware('job_seeker', \App\Http\Middleware\JobSeekerMiddleware::class);
        $router->aliasMiddleware('admin', \App\Http\Middleware\AdminMiddleware::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
