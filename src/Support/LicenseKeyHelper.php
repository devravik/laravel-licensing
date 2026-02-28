<?php

namespace DevRavik\LaravelLicensing\Support;

use DevRavik\LaravelLicensing\Contracts\LicenseContract;
use DevRavik\LaravelLicensing\Contracts\LicenseManagerContract;

/**
 * Utility helper class for license key operations in CLI contexts.
 */
class LicenseKeyHelper
{
    /**
     * Mask a license key for safe display.
     *
     * Shows first and last N characters, masking the middle portion.
     *
     * @param  string  $key  The license key to mask
     * @param  int  $visibleChars  Number of characters to show at start and end (default: 4)
     * @return string Masked key (e.g., "abcd...xyz1")
     */
    public static function mask(string $key, int $visibleChars = 4): string
    {
        $length = strlen($key);

        if ($length <= ($visibleChars * 2)) {
            // Key is too short to mask meaningfully
            return str_repeat('*', $length);
        }

        $start = substr($key, 0, $visibleChars);
        $end = substr($key, -$visibleChars);

        return $start.'...'.$end;
    }

    /**
     * Resolve a license by key or ID.
     *
     * @param  string  $keyOrId  License key (string) or ID (numeric string)
     * @param  LicenseManagerContract|null  $manager  License manager instance (optional, will resolve if null)
     * @return LicenseContract|null The license instance or null if not found
     */
    public static function resolveLicense(string $keyOrId, ?LicenseManagerContract $manager = null): ?LicenseContract
    {
        // If it's numeric, treat as ID
        if (is_numeric($keyOrId)) {
            $licenseModelClass = config('license.license_model');

            return $licenseModelClass::find((int) $keyOrId);
        }

        // Otherwise, treat as key and use LicenseManager
        if ($manager === null) {
            $manager = app(LicenseManagerContract::class);
        }

        return $manager->find($keyOrId);
    }
}
