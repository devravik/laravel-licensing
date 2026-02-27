<?php

namespace DevRavik\LaravelLicensing\Events;

use DevRavik\LaravelLicensing\Contracts\ActivationContract;
use DevRavik\LaravelLicensing\Contracts\LicenseContract;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after a license is successfully activated against a binding identifier.
 *
 * Useful for: logging activations, sending confirmation emails, updating
 * analytics dashboards, enforcing business rules on first activation.
 */
class LicenseActivated
{
    use Dispatchable, SerializesModels;

    /**
     * The license that was activated.
     */
    public LicenseContract $license;

    /**
     * The newly created activation record.
     */
    public ActivationContract $activation;

    /**
     * @param  LicenseContract  $license  The license being activated.
     * @param  ActivationContract  $activation  The created activation record.
     */
    public function __construct(LicenseContract $license, ActivationContract $activation)
    {
        $this->license = $license;
        $this->activation = $activation;
    }
}
