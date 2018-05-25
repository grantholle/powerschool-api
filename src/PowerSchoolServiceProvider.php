<?php

namespace GrantHolle\PowerSchool;

use Illuminate\Support\ServiceProvider;
use GrantHolle\PowerSchool\Api\RequestBuilder;
use GrantHolle\PowerSchool\Commands\ClearCache;

class PowerSchoolServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(RequestBuilder::class, function($app) {
            return new RequestBuilder(config('powerschool.server_address'), config('powerschool.client_id'), config('powerschool.client_secret'));
        });
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish the configuration
        $this->publishes([
            __DIR__ . '/config.php' => config_path('powerschool.php'),
        ], 'config');

        // Load routes
        // $this->loadRoutesFrom(__DIR__ . '/routes.php');

        // Commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ClearCache::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [RequestBuilder::class];
    }
}
