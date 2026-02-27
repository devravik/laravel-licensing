<?php

namespace DevRavik\LaravelLicensing\Services;

use Carbon\Carbon;
use DevRavik\LaravelLicensing\Contracts\LicenseContract;
use Illuminate\Database\Eloquent\Model;

/**
 * Fluent builder for constructing and persisting a new license.
 *
 * Usage:
 *   License::for($user)
 *       ->product('pro')
 *       ->seats(5)
 *       ->expiresInDays(365)
 *       ->create();
 */
class LicenseBuilder
{
    /**
     * The owner model this license will be associated with.
     */
    protected Model $owner;

    /**
     * The product name or tier identifier.
     */
    protected ?string $product = null;

    /**
     * Maximum number of concurrent activations.
     */
    protected int $seats = 1;

    /**
     * Explicit expiration date/time, or null for "use config default".
     */
    protected ?Carbon $expiresAt = null;

    /**
     * @param  Model  $owner  The owner model.
     * @param  LicenseManager  $manager  The manager instance that will persist the license.
     */
    public function __construct(
        Model $owner,
        protected LicenseManager $manager
    ) {
        $this->owner = $owner;
    }

    // -------------------------------------------------------------------------
    // Builder Methods
    // -------------------------------------------------------------------------

    /**
     * Set the product name or tier for this license.
     *
     * @param  string  $product  E.g. 'basic', 'pro', 'enterprise'
     */
    public function product(string $product): static
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Set the maximum number of concurrent activation bindings.
     *
     * @param  int  $count  Must be >= 1.
     *
     * @throws \InvalidArgumentException if count is less than 1.
     */
    public function seats(int $count): static
    {
        if ($count < 1) {
            throw new \InvalidArgumentException(
                "Seat count must be at least 1. Got: {$count}."
            );
        }

        $this->seats = $count;

        return $this;
    }

    /**
     * Set the expiration to $days days from now.
     *
     * @param  int  $days  Number of days from the current time. Must be >= 1.
     *
     * @throws \InvalidArgumentException if days is less than 1.
     */
    public function expiresInDays(int $days): static
    {
        if ($days < 1) {
            throw new \InvalidArgumentException(
                "Expiry days must be at least 1. Got: {$days}."
            );
        }

        $this->expiresAt = now()->addDays($days);

        return $this;
    }

    /**
     * Set an explicit expiration Carbon date/time.
     *
     * @param  Carbon  $date  Must be in the future.
     *
     * @throws \InvalidArgumentException if the date is in the past.
     */
    public function expiresAt(Carbon $date): static
    {
        if ($date->isPast()) {
            throw new \InvalidArgumentException(
                'Expiration date must be in the future.'
            );
        }

        $this->expiresAt = $date;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Terminal Method
    // -------------------------------------------------------------------------

    /**
     * Persist the license and return the model instance.
     *
     * The returned model has a transient `key` attribute attached
     * that holds the plaintext key. This attribute is NOT stored in the
     * database (only a hash is stored) and will NOT be present on any
     * subsequent retrieval.
     *
     * @return LicenseContract The persisted license model with the raw key set.
     *
     * @throws \InvalidArgumentException if product has not been set.
     */
    public function create(): LicenseContract
    {
        if ($this->product === null) {
            throw new \InvalidArgumentException(
                'A product must be set before calling create(). Use ->product("name").'
            );
        }

        // Resolve expiration: use builder value, or fall back to config default,
        // or null (license never expires).
        $expiresAt = $this->expiresAt;

        if ($expiresAt === null) {
            $defaultDays = config('license.default_expiry_days');
            if ($defaultDays !== null) {
                $expiresAt = now()->addDays((int) $defaultDays);
            }
        }

        return $this->manager->createLicense(
            owner: $this->owner,
            product: $this->product,
            seats: $this->seats,
            expiresAt: $expiresAt,
        );
    }

    // -------------------------------------------------------------------------
    // Inspection Helpers (for testing or logging)
    // -------------------------------------------------------------------------

    /**
     * Return the current builder state as an array.
     * Useful for debugging and logging during development.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'owner_type' => get_class($this->owner),
            'owner_id' => $this->owner->getKey(),
            'product' => $this->product,
            'seats' => $this->seats,
            'expires_at' => $this->expiresAt?->toIso8601String(),
        ];
    }
}
