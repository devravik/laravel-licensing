<?php

namespace DevRavik\LaravelLicensing\Events;

use DevRavik\LaravelLicensing\Contracts\LicenseContract;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a scheduled command detects that a license has fully expired.
 *
 * This event is NOT dispatched by LicenseManager during a validation call.
 * It is designed to be dispatched from a scheduled Artisan command or job
 * that sweeps the licenses table for expired records.
 *
 * Useful for: sending renewal reminder emails, disabling account features,
 * archiving records.
 *
 * Example scheduler setup:
 *
 *   Schedule::call(function () {
 *       License::query()
 *           ->whereNull('revoked_at')
 *           ->whereNotNull('expires_at')
 *           ->where('expires_at', '<=', now()->subDays(config('license.grace_period_days')))
 *           ->each(fn ($license) => event(new LicenseExpired($license)));
 *   })->daily();
 */
class LicenseExpired
{
    use Dispatchable, SerializesModels;

    /**
     * The expired license instance.
     */
    public LicenseContract $license;

    /**
     * @param  LicenseContract  $license  The license that has expired.
     */
    public function __construct(LicenseContract $license)
    {
        $this->license = $license;
    }
}
