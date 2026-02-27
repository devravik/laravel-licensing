<?php

namespace DevRavik\LaravelLicensing;

use DevRavik\LaravelLicensing\Console\LicenseStatusCommand;
use DevRavik\LaravelLicensing\Contracts\KeyGeneratorContract;
use DevRavik\LaravelLicensing\Contracts\LicenseManagerContract;
use DevRavik\LaravelLicensing\Http\Middleware\CheckLicense;
use DevRavik\LaravelLicensing\Http\Middleware\CheckValidLicense;
use DevRavik\LaravelLicensing\Services\KeyGenerator;
use DevRavik\LaravelLicensing\Services\LicenseManager;
use DevRavik\LaravelLicensing\Services\LicenseValidator;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class LicenseServiceProvider extends ServiceProvider
{
    /**
     * All package assets available for publishing.
     */
    private const TAG_CONFIG = 'license-config';

    private const TAG_MIGRATIONS = 'license-migrations';

    // -------------------------------------------------------------------------
    // Register
    // -------------------------------------------------------------------------

    /**
     * Register package bindings into the Laravel IoC container.
     */
    public function register(): void
    {
        // Merge the package config so consumers can use config('license.*')
        // even before they publish the config file.
        $this->mergeConfigFrom(
            __DIR__.'/../config/license.php',
            'license'
        );

        // Bind the key generator — swappable by consumers.
        $this->app->bind(
            KeyGeneratorContract::class,
            KeyGenerator::class
        );

        // Bind the license validator — swappable by consumers.
        $this->app->bind(LicenseValidator::class);

        // Bind the license manager as a singleton — one instance per request.
        // Consumers can override this binding in their own AppServiceProvider.
        $this->app->singleton(
            LicenseManagerContract::class,
            function ($app) {
                return new LicenseManager(
                    keyGenerator: $app->make(KeyGeneratorContract::class),
                    hasher: $app['hash'],
                    events: $app['events'],
                    validator: $app->make(LicenseValidator::class),
                );
            }
        );

        // Register a short accessor key so the facade can resolve it.
        $this->app->alias(LicenseManagerContract::class, 'license');
    }

    // -------------------------------------------------------------------------
    // Boot
    // -------------------------------------------------------------------------

    /**
     * Bootstrap package services after all providers are registered.
     */
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerMiddleware();
        $this->registerCommands();
    }

    /**
     * Register all publishable package assets.
     */
    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        // Publish config/license.php
        $this->publishes([
            __DIR__.'/../config/license.php' => config_path('license.php'),
        ], self::TAG_CONFIG);

        // Publish migration stubs — consumers may edit before running.
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], self::TAG_MIGRATIONS);
    }

    /**
     * Register the package's Artisan commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                LicenseStatusCommand::class,
            ]);
        }
    }

    /**
     * Register middleware aliases with Laravel's router.
     */
    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];

        $router->aliasMiddleware('license', CheckLicense::class);
        $router->aliasMiddleware('license.valid', CheckValidLicense::class);
    }
}
