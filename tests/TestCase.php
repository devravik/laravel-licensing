<?php

namespace DevRavik\LaravelLicensing\Tests;

use DevRavik\LaravelLicensing\LicenseServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function defineDatabaseMigrations(): void
    {
        // loadMigrationsFrom() is smart: when RefreshDatabase is used and
        // migrate:fresh hasn't run yet, it registers the path with the migrator
        // so that migrate:fresh will include the package migrations automatically.
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed(): void
    {
        // loadLaravelMigrations() always runs artisan migrate directly, so we
        // call it AFTER migrate:fresh to ensure the Testbench users table is
        // created in the refreshed database.
        $this->loadLaravelMigrations();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LicenseServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');

        $driver = env('TEST_DB_DRIVER', 'sqlite');

        if ($driver === 'sqlite') {
            // Default: SQLite in-memory — no external service required.
            config()->set('database.connections.testing', [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ]);
        } else {
            // MySQL / PostgreSQL: driven entirely by env vars in CI.
            config()->set('database.connections.testing', [
                'driver'    => $driver,
                'host'      => env('TEST_DB_HOST', '127.0.0.1'),
                'port'      => env('TEST_DB_PORT', '3306'),
                'database'  => env('TEST_DB_DATABASE', 'laravel_licensing_test'),
                'username'  => env('TEST_DB_USERNAME', 'root'),
                'password'  => env('TEST_DB_PASSWORD', ''),
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
            ]);
        }

        // Use low bcrypt cost for fast test runs.
        config()->set('hashing.bcrypt.rounds', 4);
    }

    /**
     * Create a fake User model for license ownership tests.
     * Uses orchestra/testbench's built-in User model.
     */
    protected function createUser(): \Illuminate\Foundation\Auth\User
    {
        return \Orchestra\Testbench\Factories\UserFactory::new()->create();
    }
}
