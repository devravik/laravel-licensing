<?php

namespace DevRavik\LaravelLicensing\Exceptions;

/**
 * Thrown when a license key cannot be found in the database.
 *
 * The message intentionally does not reveal whether the key was
 * "wrong" vs. "not found" to prevent oracle attacks.
 */
class InvalidLicenseException extends LicenseManagerException
{
    protected int $statusCode = 404;

    /**
     * Create an exception for a given raw key attempt.
     *
     * The key is intentionally redacted from the message to prevent
     * accidental logging of sensitive data in error tracking services.
     */
    public static function forKey(string $key): static
    {
        return new static('The provided license key is invalid.');
    }
}
