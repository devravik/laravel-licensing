<?php

namespace DevRavik\LaravelLicensing\Contracts;

/**
 * Defines the contract for generating raw license key strings.
 *
 * The returned string is the plaintext key — it is hashed by the
 * LicenseManager before being stored if hash_keys is enabled.
 *
 * @deprecated Use LicenseGeneratorInterface instead. This interface is maintained
 *            for backward compatibility only and will be removed in v2.0.
 */
interface KeyGeneratorContract
{
    /**
     * Generate a new cryptographically secure license key string.
     *
     * The returned key must contain only printable ASCII characters
     * suitable for display and transmission over HTTP.
     *
     * @param  int  $length  Number of characters in the generated key.
     * @return string The raw (unhashed) license key.
     *
     * @deprecated Use LicenseGeneratorInterface::generate() instead.
     */
    public function generate(int $length): string;
}
