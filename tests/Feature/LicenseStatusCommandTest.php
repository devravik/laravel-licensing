<?php

namespace DevRavik\LaravelLicensing\Tests\Feature;

use DevRavik\LaravelLicensing\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class LicenseStatusCommandTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Success path — both tables exist
    // -------------------------------------------------------------------------

    public function test_command_returns_success_when_both_tables_exist(): void
    {
        // Both tables are created by the migrations loaded in TestCase.
        $this->artisan('license:status')
             ->assertSuccessful();
    }

    public function test_command_output_contains_configuration_section(): void
    {
        $this->artisan('license:status')
             ->expectsOutputToContain('Configuration')
             ->assertSuccessful();
    }

    public function test_command_output_contains_database_tables_section(): void
    {
        $this->artisan('license:status')
             ->expectsOutputToContain('Database Tables')
             ->assertSuccessful();
    }

    public function test_command_output_shows_correct_key_length_from_config(): void
    {
        config()->set('license.key_length', 32);

        $this->artisan('license:status')
             ->expectsOutputToContain('32')
             ->assertSuccessful();
    }

    public function test_command_output_shows_correct_grace_period_from_config(): void
    {
        config()->set('license.grace_period_days', 14);

        $this->artisan('license:status')
             ->expectsOutputToContain('14')
             ->assertSuccessful();
    }

    public function test_command_output_confirms_package_is_configured_correctly(): void
    {
        $this->artisan('license:status')
             ->expectsOutputToContain('Package is installed and configured correctly')
             ->assertSuccessful();
    }

    // -------------------------------------------------------------------------
    // Failure path — tables missing
    // -------------------------------------------------------------------------

    public function test_command_returns_failure_when_licenses_table_is_missing(): void
    {
        Schema::drop('license_activations');
        Schema::drop('licenses');

        $this->artisan('license:status')
             ->assertFailed();

        // Re-create so other tests are not affected (RefreshDatabase handles
        // this across test files, but be explicit for within-file clarity).
    }

    public function test_command_warns_when_tables_are_missing(): void
    {
        Schema::drop('license_activations');
        Schema::drop('licenses');

        $this->artisan('license:status')
             ->expectsOutputToContain('missing')
             ->assertFailed();
    }

    public function test_command_output_shows_licenses_table_name(): void
    {
        $this->artisan('license:status')
             ->expectsOutputToContain('licenses')
             ->assertSuccessful();
    }

    public function test_command_output_shows_license_activations_table_name(): void
    {
        $this->artisan('license:status')
             ->expectsOutputToContain('license_activations')
             ->assertSuccessful();
    }
}
