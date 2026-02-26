<?php

namespace Bellesoft\LicenseClient;

use Illuminate\Support\ServiceProvider;
use Bellesoft\LicenseClient\Services\LicenseClientService;
class LicenseClientServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->registerConfigs();
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/config.php' => $this->app->configPath('license-client.php'),
        ], 'license-client-config');
    }

    /**
     * Register the configs.
     */
    private function registerConfigs(): void
    {

        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'license-client');
    }
}
