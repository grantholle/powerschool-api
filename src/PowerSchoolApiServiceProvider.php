<?php

namespace GrantHolle\PowerSchool\Api;

use GrantHolle\PowerSchool\Api\Commands\Authenticate;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use GrantHolle\PowerSchool\Api\Commands\ClearCache;

class PowerSchoolApiServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(RequestBuilder::class, fn () => new RequestBuilder(
            config('powerschool.server_address'),
            config('powerschool.client_id'),
            config('powerschool.client_secret'),
            config('powerschool.cache_key')
        ));

        $this->mergeConfigFrom(__DIR__ . '/config.php', 'powerschool');
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish the configuration and migration
        $this->publishes([
            __DIR__ . '/config.php' => config_path('powerschool.php'),
        ], ['config', 'powerschool-config']);

        // Commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ClearCache::class,
                Authenticate::class
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [RequestBuilder::class];
    }
}
