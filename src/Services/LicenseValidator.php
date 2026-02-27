<?php

namespace DevRavik\LaravelLicensing\Services;

use DevRavik\LaravelLicensing\Contracts\LicenseContract;
use DevRavik\LaravelLicensing\Exceptions\LicenseExpiredException;
use DevRavik\LaravelLicensing\Exceptions\LicenseManagerException;
use DevRavik\LaravelLicensing\Exceptions\LicenseRevokedException;

/**
 * Validates the business state of a resolved license model.
 *
 * This class is used internally by LicenseManager after the license has
 * been located by key. It does NOT perform key lookup — that is the
 * LicenseManager's responsibility.
 */
class LicenseValidator
{
    /**
     * Assert that a resolved license is in a valid state.
     *
     * @throws LicenseRevokedException
     * @throws LicenseExpiredException
     */
    public function assertValid(LicenseContract $license): void
    {
        if ($license->isRevoked()) {
            throw LicenseRevokedException::forLicense($license);
        }

        if ($license->isExpired() && ! $license->isInGracePeriod()) {
            throw LicenseExpiredException::forLicense($license);
        }
    }

    /**
     * Check validity without throwing — returns bool.
     */
    public function isValid(LicenseContract $license): bool
    {
        try {
            $this->assertValid($license);

            return true;
        } catch (LicenseManagerException) {
            return false;
        }
    }
}
