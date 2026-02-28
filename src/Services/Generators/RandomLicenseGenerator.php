<?php

namespace DevRavik\LaravelLicensing\Services\Generators;

use DevRavik\LaravelLicensing\Contracts\LicenseGeneratorInterface;

/**
 * Generates cryptographically secure random license key strings using PHP's
 * random_bytes() function, which reads from the OS CSPRNG.
 *
 * The output is a lowercase hexadecimal string. The payload parameter is
 * ignored for random generation — only the configured key_length is used.
 */
class RandomLicenseGenerator implements LicenseGeneratorInterface
{
    /**
     * Generate a secure random license key.
     *
     * The payload is ignored for random generation. The key length is
     * determined by the 'license.key_length' config value.
     *
     * @param  array<string, mixed>  $payload  Ignored for random generation.
     * @return string Lowercase hex string of the configured length.
     *
     * @throws \InvalidArgumentException if length is out of the acceptable range.
     */
    public function generate(array $payload): string
    {
        $length = (int) config('license.key_length', 32);

        if ($length < 16 || $length > 128) {
            throw new \InvalidArgumentException(
                "License key length must be between 16 and 128. Got: {$length}."
            );
        }

        // We need ceil($length / 2) bytes to produce $length hex characters.
        // bin2hex() converts each byte to two hex chars, so we truncate to
        // the exact requested length.
        $bytesNeeded = (int) ceil($length / 2);
        $hex = bin2hex(random_bytes($bytesNeeded));

        return substr($hex, 0, $length);
    }
}
