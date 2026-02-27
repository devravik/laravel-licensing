<?php

namespace DevRavik\LaravelLicensing\Exceptions;

use Carbon\Carbon;
use DevRavik\LaravelLicensing\Contracts\LicenseContract;

/**
 * Thrown when a license has expired beyond its configured grace period.
 *
 * This exception carries the expiration timestamp for use in error responses
 * or user-facing messages.
 */
class LicenseExpiredException extends LicenseManagerException
{
    protected int $statusCode = 403;

    /**
     * The timestamp when the license expired.
     */
    protected Carbon $expiredAt;

    /**
     * Return the expiration timestamp for this license.
     */
    public function getExpiredAt(): Carbon
    {
        return $this->expiredAt;
    }

    /**
     * Create an exception from a resolved license model.
     */
    public static function forLicense(LicenseContract $license): static
    {
        $expiredAt = $license->getExpiresAt();

        $instance            = new static(
            "This license expired on {$expiredAt->toFormattedDateString()} and is no longer valid."
        );
        $instance->expiredAt = $expiredAt;

        return $instance;
    }
}
