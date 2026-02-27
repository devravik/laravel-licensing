<?php

namespace DevRavik\LaravelLicensing\Contracts;

/**
 * Defines the contract for generating raw license key strings.
 *
 * The returned string is the plaintext key — it is hashed by the
 * LicenseManager before being stored if hash_keys is enabled.
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
     */
    public function generate(int $length): string;
}
