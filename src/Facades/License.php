<?php

namespace DevRavik\LaravelLicensing\Facades;

use DevRavik\LaravelLicensing\Contracts\ActivationContract;
use DevRavik\LaravelLicensing\Contracts\LicenseContract;
use DevRavik\LaravelLicensing\Services\LicenseBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * The License facade provides a static interface to the LicenseManager.
 *
 * @method static LicenseBuilder for(Model $owner)
 * @method static LicenseContract validate(string $key)
 * @method static ActivationContract activate(string $key, string $binding)
 * @method static bool deactivate(string $key, string $binding)
 * @method static bool revoke(string $key)
 * @method static LicenseContract|null find(string $key)
 *
 * @see \DevRavik\LaravelLicensing\Services\LicenseManager
 */
class License extends Facade
{
    /**
     * Get the registered name of the component in the IoC container.
     *
     * This matches the alias registered in LicenseServiceProvider::register().
     */
    protected static function getFacadeAccessor(): string
    {
        return 'license';
    }
}
