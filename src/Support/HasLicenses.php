<?php

namespace DevRavik\LaravelLicensing\Support;

use DevRavik\LaravelLicensing\Models\License;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasLicenses
{
    /**
     * Get all licenses belonging to this model.
     */
    public function licenses(): MorphMany
    {
        return $this->morphMany(
            config('license.license_model', License::class),
            'owner'
        );
    }

    /**
     * Return all currently valid (non-revoked, non-fully-expired) licenses.
     *
     * A license is considered active if it is:
     *   - not revoked
     *   - not yet expired, OR expired but still within the configured grace period
     *
     * This matches the behaviour of LicenseContract::isValid().
     */
    public function activeLicenses(): Collection
    {
        $graceDays = (int) config('license.grace_period_days', 0);

        return $this->licenses()
            ->whereNull('revoked_at')
            ->where(function ($query) use ($graceDays) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now())
                    ->when($graceDays > 0, function ($q) use ($graceDays) {
                        // Also include licenses expired within the grace window.
                        $q->orWhere('expires_at', '>', now()->subDays($graceDays));
                    });
            })
            ->get();
    }

    /**
     * Return whether the model owns any valid license for a given product.
     *
     * Respects the configured grace period — a license expired within the grace
     * window is still considered valid, consistent with LicenseContract::isValid().
     */
    public function hasLicenseForProduct(string $product): bool
    {
        $graceDays = (int) config('license.grace_period_days', 0);

        return $this->licenses()
            ->where('product', $product)
            ->whereNull('revoked_at')
            ->where(function ($query) use ($graceDays) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now())
                    ->when($graceDays > 0, function ($q) use ($graceDays) {
                        $q->orWhere('expires_at', '>', now()->subDays($graceDays));
                    });
            })
            ->exists();
    }
}
