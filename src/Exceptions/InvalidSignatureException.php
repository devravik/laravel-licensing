<?php

namespace DevRavik\LaravelLicensing\Exceptions;

/**
 * Thrown when a license signature verification fails.
 *
 * This exception indicates that the Ed25519 signature on a signed license
 * key is invalid, tampered, or cannot be verified.
 */
class InvalidSignatureException extends LicenseManagerException
{
    /**
     * Create an exception for a failed signature verification.
     */
    public static function verificationFailed(string $reason = 'Signature verification failed'): self
    {
        return new self($reason);
    }

    /**
     * Get the HTTP status code for this exception.
     */
    public function getStatusCode(): int
    {
        return 403; // Forbidden
    }
}
