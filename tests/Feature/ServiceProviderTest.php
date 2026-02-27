<?php

namespace DevRavik\LaravelLicensing\Tests\Feature;

use DevRavik\LaravelLicensing\Contracts\KeyGeneratorContract;
use DevRavik\LaravelLicensing\Contracts\LicenseManagerContract;
use DevRavik\LaravelLicensing\Facades\License;
use DevRavik\LaravelLicensing\Services\KeyGenerator;
use DevRavik\LaravelLicensing\Services\LicenseManager;
use DevRavik\LaravelLicensing\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    /**
     * Override parent setUp to skip migration loading — these tests do not
     * touch the database; they only verify IoC bindings, config merging,
     * the facade, and middleware alias registration.
     */
    protected function setUp(): void
    {
        // Call Orchestra's setUp directly, bypassing loadMigrationsFrom().
        \Orchestra\Testbench\TestCase::setUp();
    }

    // -------------------------------------------------------------------------
    // Container Bindings
    // -------------------------------------------------------------------------

    public function test_license_manager_contract_is_bound_as_singleton(): void
    {
        $a = $this->app->make(LicenseManagerContract::class);
        $b = $this->app->make(LicenseManagerContract::class);

        $this->assertInstanceOf(LicenseManager::class, $a);
        $this->assertSame($a, $b, 'LicenseManager should be a singleton — same instance on repeated resolution.');
    }

    public function test_key_generator_contract_is_bound_as_transient(): void
    {
        $a = $this->app->make(KeyGeneratorContract::class);
        $b = $this->app->make(KeyGeneratorContract::class);

        $this->assertInstanceOf(KeyGenerator::class, $a);
        $this->assertNotSame($a, $b, 'KeyGenerator should NOT be a singleton — a new instance each time.');
    }

    public function test_license_alias_resolves_to_license_manager(): void
    {
        $manager = $this->app->make('license');

        $this->assertInstanceOf(LicenseManager::class, $manager);
    }

    public function test_license_alias_is_same_singleton_as_contract_binding(): void
    {
        $fromContract = $this->app->make(LicenseManagerContract::class);
        $fromAlias = $this->app->make('license');

        $this->assertSame($fromContract, $fromAlias, "'license' alias must resolve to the same singleton as LicenseManagerContract.");
    }

    // -------------------------------------------------------------------------
    // Facade
    // -------------------------------------------------------------------------

    public function test_facade_resolves_to_license_manager(): void
    {
        $this->assertInstanceOf(
            LicenseManager::class,
            License::getFacadeRoot()
        );
    }

    public function test_facade_root_is_same_instance_as_container_singleton(): void
    {
        $fromContainer = $this->app->make(LicenseManagerContract::class);

        $this->assertSame($fromContainer, License::getFacadeRoot());
    }

    // -------------------------------------------------------------------------
    // Config
    // -------------------------------------------------------------------------

    public function test_config_is_merged_and_accessible(): void
    {
        $this->assertNotNull(config('license'));
        $this->assertSame(32, config('license.key_length'));
        $this->assertTrue(config('license.hash_keys'));
        $this->assertSame(365, config('license.default_expiry_days'));
        $this->assertSame(7, config('license.grace_period_days'));
        $this->assertSame(\DevRavik\LaravelLicensing\Models\License::class, config('license.license_model'));
        $this->assertSame(\DevRavik\LaravelLicensing\Models\Activation::class, config('license.activation_model'));
    }

    // -------------------------------------------------------------------------
    // Middleware Aliases
    // -------------------------------------------------------------------------

    public function test_license_middleware_alias_is_registered(): void
    {
        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app['router'];

        $middlewareMap = $router->getMiddleware();

        $this->assertArrayHasKey('license', $middlewareMap);
        $this->assertSame(\DevRavik\LaravelLicensing\Http\Middleware\CheckLicense::class, $middlewareMap['license']);
    }

    public function test_license_valid_middleware_alias_is_registered(): void
    {
        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app['router'];

        $middlewareMap = $router->getMiddleware();

        $this->assertArrayHasKey('license.valid', $middlewareMap);
        $this->assertSame(\DevRavik\LaravelLicensing\Http\Middleware\CheckValidLicense::class, $middlewareMap['license.valid']);
    }
}
