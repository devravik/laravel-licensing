<?php

namespace DevRavik\LaravelLicensing\Services;

use DevRavik\LaravelLicensing\Contracts\KeyGeneratorContract;

/**
 * Generates cryptographically secure license key strings using PHP's
 * random_bytes() function, which reads from the OS CSPRNG.
 *
 * The output is a lowercase hexadecimal string. 32 hex characters
 * represent 128 bits of entropy, which is sufficient for license keys
 * and is consistent with UUID security levels.
 *
 * @deprecated Use RandomLicenseGenerator instead. This class is maintained
 *            for backward compatibility only and will be removed in v2.0.
 */
class KeyGenerator implements KeyGeneratorContract
{
    /**
     * Generate a secure random license key of the given character length.
     *
     * @param  int  $length  Number of hex characters in the output key.
     *                       Must be between 16 and 128.
     * @return string Lowercase hex string of the requested length.
     *
     * @throws \InvalidArgumentException if length is out of the acceptable range.
     */
    public function generate(int $length): string
    {
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
