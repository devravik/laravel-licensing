<?php

namespace DevRavik\LaravelLicensing\Exceptions;

use DevRavik\LaravelLicensing\Contracts\LicenseContract;

/**
 * Thrown when attempting to activate a license against a binding that
 * is already registered for that license.
 *
 * This is a 409 Conflict because the resource (activation record) already
 * exists — it is not an authorization failure.
 */
class LicenseAlreadyActivatedException extends LicenseManagerException
{
    protected int $statusCode = 409;

    /**
     * The license on which the conflict occurred.
     */
    protected LicenseContract $license;

    /**
     * The duplicate binding identifier.
     */
    protected string $binding;

    /**
     * Return the license on which the activation conflict occurred.
     */
    public function getLicense(): LicenseContract
    {
        return $this->license;
    }

    /**
     * Return the binding that caused the conflict.
     */
    public function getBinding(): string
    {
        return $this->binding;
    }

    /**
     * Create an exception for a specific license and binding.
     */
    public static function forBinding(LicenseContract $license, string $binding): static
    {
        $instance          = new static(
            "The binding '{$binding}' is already activated on this license."
        );
        $instance->license = $license;
        $instance->binding = $binding;

        return $instance;
    }
}
