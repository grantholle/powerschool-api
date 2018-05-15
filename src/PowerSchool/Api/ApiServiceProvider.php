<?php

namespace PowerSchool\Api;

use Illuminate\Support\ServiceProvider;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('powerschool', function() {
            return $this->app->make(RequestBuilder::class);
        });
    }
}
