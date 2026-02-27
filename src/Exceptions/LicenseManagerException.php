<?php

namespace DevRavik\LaravelLicensing\Exceptions;

use RuntimeException;

/**
 * Base exception for all devravik/laravel-licensing errors.
 *
 * Catching this class will catch any exception thrown by the package.
 * Use the concrete subclasses for granular error handling.
 */
class LicenseManagerException extends RuntimeException
{
    /**
     * The HTTP status code that best represents this error.
     */
    protected int $statusCode = 500;

    /**
     * Return the HTTP status code for this exception.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Create a new instance with a custom message and status code.
     *
     * Useful for creating ad-hoc exceptions in tests or extension code.
     */
    public static function make(string $message, int $statusCode = 500): static
    {
        $instance = new static($message);
        $instance->statusCode = $statusCode;

        return $instance;
    }
}
