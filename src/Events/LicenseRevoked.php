<?php

namespace DevRavik\LaravelLicensing\Events;

use DevRavik\LaravelLicensing\Contracts\LicenseContract;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired immediately after a license's revoked_at timestamp is set.
 *
 * After this event, all subsequent validate() and activate() calls on
 * this license's key will throw LicenseRevokedException.
 *
 * Useful for: notifying the affected user, logging the revocation actor,
 * canceling related subscriptions or access grants.
 */
class LicenseRevoked
{
    use Dispatchable, SerializesModels;

    /**
     * The revoked license instance.
     */
    public LicenseContract $license;

    /**
     * @param  LicenseContract  $license  The license that was revoked.
     */
    public function __construct(LicenseContract $license)
    {
        $this->license = $license;
    }
}
