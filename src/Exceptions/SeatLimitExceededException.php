<?php

namespace DevRavik\LaravelLicensing\Exceptions;

use DevRavik\LaravelLicensing\Contracts\LicenseContract;

/**
 * Thrown when a license has no remaining activation seats.
 *
 * This exception carries the seat counts for use in detailed error responses.
 */
class SeatLimitExceededException extends LicenseManagerException
{
    protected int $statusCode = 422;

    /**
     * The maximum number of seats this license allows.
     */
    protected int $seatsAllowed;

    /**
     * The number of seats currently in use.
     */
    protected int $seatsUsed;

    /**
     * Return the maximum number of allowed seats.
     */
    public function getSeatsAllowed(): int
    {
        return $this->seatsAllowed;
    }

    /**
     * Return the number of seats currently in use.
     */
    public function getSeatsUsed(): int
    {
        return $this->seatsUsed;
    }

    /**
     * Create an exception from a resolved license model.
     */
    public static function forLicense(LicenseContract $license): static
    {
        $allowed = $license->getSeats();
        $used    = $license->countActivations();

        $instance               = new static(
            "This license allows {$allowed} activation(s) and all {$used} seats are currently in use. "
            . "Please deactivate an existing binding before adding a new one."
        );
        $instance->seatsAllowed = $allowed;
        $instance->seatsUsed    = $used;

        return $instance;
    }
}
