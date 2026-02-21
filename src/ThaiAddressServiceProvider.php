<?php

namespace Kingw1\ThaiAddress;

use Illuminate\Support\ServiceProvider;
use Kingw1\ThaiAddress\Commands\InstallThaiAddresses;
use Kingw1\ThaiAddress\Commands\SyncThaiAddresses;

class ThaiAddressServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // $this->publishes([
        //     __DIR__ . '/../database/migrations/' => database_path('migrations'),
        // ], 'thai-address-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncThaiAddresses::class,
                InstallThaiAddresses::class,
            ]);
        }
    }

    public function register()
    {
        //
    }
}
