<?php

namespace DevRavik\LaravelLicensing\Contracts;

/**
 * Defines the contract for generating license keys using different strategies.
 *
 * The returned string is the plaintext key — it is hashed by the
 * LicenseManager before being stored if hash_keys is enabled.
 *
 * For random generation: the payload is ignored and a random key is generated.
 * For signed generation: the payload is used to create a signed license key.
 */
interface LicenseGeneratorInterface
{
    /**
     * Generate a new license key string.
     *
     * The returned key must contain only printable ASCII characters
     * suitable for display and transmission over HTTP.
     *
     * @param  array<string, mixed>  $payload  License metadata (product, seats, expires_at, owner info, etc.)
     * @return string The raw (unhashed) license key.
     */
    public function generate(array $payload): string;
}
