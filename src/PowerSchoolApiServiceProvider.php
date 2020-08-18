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
        $this->app->bind(RequestBuilder::class, function ($app) {
            return new RequestBuilder(
                config('powerschool.server_address'),
                config('powerschool.client_id'),
                config('powerschool.client_secret')
            );
        });
    }

    /**
     * Perform post-registration booting of services.
     *
     * @param Filesystem $filesystem
     * @return void
     */
    public function boot(Filesystem $filesystem)
    {
        // Publish the configuration and migration
        $this->publishes([
            __DIR__ . '/config.php' => config_path('powerschool.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/database/migrations/add_open_id_column_to_users_table.php.stub' => $this->getMigrationFileName($filesystem),
        ], 'migrations');

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
    public function provides()
    {
        return [RequestBuilder::class];
    }

    /**
     * Returns existing migration file if found, else uses the current timestamp.
     *
     * @param Filesystem $filesystem
     * @return string
     */
    protected function getMigrationFileName(Filesystem $filesystem): string
    {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath(DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR))
            ->flatMap(function ($path) use ($filesystem) {
                return glob($path . '*_add_open_id_column_to_users_table.php');
            })->push($this->app->databasePath("migrations/{$timestamp}_add_open_id_column_to_users_table.php"))
            ->first();
    }
}