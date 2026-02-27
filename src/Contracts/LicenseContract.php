<?php

namespace DevRavik\LaravelLicensing\Contracts;

use Carbon\Carbon;

/**
 * Defines the required API for a license model.
 *
 * Any class implementing this contract can be used as the license_model
 * in config/license.php without modifying any package internals.
 */
interface LicenseContract
{
    // -------------------------------------------------------------------------
    // Accessors — required for middleware, exceptions, and core services
    // -------------------------------------------------------------------------

    /**
     * Return the product name or tier this license is for.
     *
     * Used by CheckLicense middleware to verify the license is for the
     * required product tier.
     */
    public function getProduct(): string;

    /**
     * Return the maximum number of allowed activation seats.
     */
    public function getSeats(): int;

    /**
     * Return the expiration timestamp, or null if the license never expires.
     */
    public function getExpiresAt(): ?Carbon;

    /**
     * Return the revocation timestamp, or null if the license is not revoked.
     */
    public function getRevokedAt(): ?Carbon;

    /**
     * Return the total number of active activation records for this license.
     *
     * Used by SeatLimitExceededException to report accurate seat usage.
     */
    public function countActivations(): int;

    // -------------------------------------------------------------------------
    // Status Checks
    // -------------------------------------------------------------------------

    /**
     * Determine whether the license is currently valid.
     *
     * A valid license is one that:
     *   - has not been revoked
     *   - has not expired beyond its grace period
     */
    public function isValid(): bool;

    /**
     * Determine whether the license has passed its expiration date.
     *
     * A license with a null expires_at is considered never-expiring
     * and should always return false from this method.
     */
    public function isExpired(): bool;

    /**
     * Determine whether the license is expired but still within
     * the configured grace window (grace_period_days).
     *
     * Returns false if grace periods are disabled (grace_period_days = 0)
     * or if the license is not expired.
     */
    public function isInGracePeriod(): bool;

    /**
     * Determine whether the license has been revoked.
     */
    public function isRevoked(): bool;

    // -------------------------------------------------------------------------
    // Seat Helpers
    // -------------------------------------------------------------------------

    /**
     * Return the number of activation seats still available.
     */
    public function seatsRemaining(): int;

    /**
     * Determine whether at least one seat is available.
     */
    public function hasAvailableSeat(): bool;

    // -------------------------------------------------------------------------
    // Grace Period Helpers
    // -------------------------------------------------------------------------

    /**
     * Return the number of days remaining in the grace period.
     *
     * Returns 0 if the license is not in a grace period.
     */
    public function graceDaysRemaining(): int;
}
