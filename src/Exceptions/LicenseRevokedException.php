<?php

namespace DevRavik\LaravelLicensing\Exceptions;

use Carbon\Carbon;
use DevRavik\LaravelLicensing\Contracts\LicenseContract;

/**
 * Thrown when a license has been explicitly revoked.
 *
 * A revoked license cannot be re-activated without administrative intervention.
 */
class LicenseRevokedException extends LicenseManagerException
{
    protected int $statusCode = 403;

    /**
     * The timestamp when the license was revoked.
     */
    protected Carbon $revokedAt;

    /**
     * Return the revocation timestamp.
     */
    public function getRevokedAt(): Carbon
    {
        return $this->revokedAt;
    }

    /**
     * Create an exception from a resolved license model.
     */
    public static function forLicense(LicenseContract $license): static
    {
        $revokedAt = $license->getRevokedAt();

        $instance            = new static(
            "This license was revoked on {$revokedAt->toFormattedDateString()} and can no longer be used."
        );
        $instance->revokedAt = $revokedAt;

        return $instance;
    }
}
