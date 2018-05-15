<?php

namespace PowerSchool;

use Illuminate\Support\ServiceProvider;
use PowerSchool\Api\RequestBuilder;

class PowerSchoolServiceProvider extends ServiceProvider
{
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
        $this->publishes([
            __DIR__ . '/config/powerschool.php' => config_path('powerschool.php'),
        ]);
    }
}
