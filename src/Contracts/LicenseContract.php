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
     *
     * @return string
     */
    public function getProduct(): string;

    /**
     * Return the maximum number of allowed activation seats.
     *
     * @return int
     */
    public function getSeats(): int;

    /**
     * Return the expiration timestamp, or null if the license never expires.
     *
     * @return Carbon|null
     */
    public function getExpiresAt(): ?Carbon;

    /**
     * Return the revocation timestamp, or null if the license is not revoked.
     *
     * @return Carbon|null
     */
    public function getRevokedAt(): ?Carbon;

    /**
     * Return the total number of active activation records for this license.
     *
     * Used by SeatLimitExceededException to report accurate seat usage.
     *
     * @return int
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
     *
     * @return bool
     */
    public function isValid(): bool;

    /**
     * Determine whether the license has passed its expiration date.
     *
     * A license with a null expires_at is considered never-expiring
     * and should always return false from this method.
     *
     * @return bool
     */
    public function isExpired(): bool;

    /**
     * Determine whether the license is expired but still within
     * the configured grace window (grace_period_days).
     *
     * Returns false if grace periods are disabled (grace_period_days = 0)
     * or if the license is not expired.
     *
     * @return bool
     */
    public function isInGracePeriod(): bool;

    /**
     * Determine whether the license has been revoked.
     *
     * @return bool
     */
    public function isRevoked(): bool;

    // -------------------------------------------------------------------------
    // Seat Helpers
    // -------------------------------------------------------------------------

    /**
     * Return the number of activation seats still available.
     *
     * @return int
     */
    public function seatsRemaining(): int;

    /**
     * Determine whether at least one seat is available.
     *
     * @return bool
     */
    public function hasAvailableSeat(): bool;

    // -------------------------------------------------------------------------
    // Grace Period Helpers
    // -------------------------------------------------------------------------

    /**
     * Return the number of days remaining in the grace period.
     *
     * Returns 0 if the license is not in a grace period.
     *
     * @return int
     */
    public function graceDaysRemaining(): int;
}
