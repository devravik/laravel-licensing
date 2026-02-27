<?php

namespace DevRavik\LaravelLicensing\Events;

use DevRavik\LaravelLicensing\Contracts\LicenseContract;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after an activation binding is removed from a license.
 *
 * The freed seat is immediately available for a new activation.
 *
 * Note: Unlike LicenseActivated, this event carries the binding string
 * directly (not an Activation model) because the activation record has
 * already been deleted from the database at this point.
 */
class LicenseDeactivated
{
    use Dispatchable, SerializesModels;

    /**
     * The license from which the binding was removed.
     *
     * @var LicenseContract
     */
    public LicenseContract $license;

    /**
     * The binding identifier that was deactivated.
     *
     * @var string
     */
    public string $binding;

    /**
     * @param  LicenseContract  $license  The license that was deactivated.
     * @param  string           $binding  The binding string that was removed.
     */
    public function __construct(LicenseContract $license, string $binding)
    {
        $this->license = $license;
        $this->binding = $binding;
    }
}
