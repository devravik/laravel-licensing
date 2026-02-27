<?php

namespace DevRavik\LaravelLicensing\Models;

use DevRavik\LaravelLicensing\Contracts\LicenseContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int                   $id
 * @property string                $key
 * @property string|null           $lookup_token
 * @property string                $product
 * @property int                   $owner_id
 * @property string                $owner_type
 * @property int                   $seats
 * @property \Carbon\Carbon|null   $expires_at
 * @property \Carbon\Carbon|null   $revoked_at
 * @property \Carbon\Carbon        $created_at
 * @property \Carbon\Carbon        $updated_at
 */
class License extends Model implements LicenseContract
{
    /**
     * The table associated with the model.
     */
    protected $table = 'licenses';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'lookup_token',
        'product',
        'owner_id',
        'owner_type',
        'seats',
        'expires_at',
        'revoked_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'seats'      => 'integer',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The polymorphic owner of this license (User, Team, etc.).
     *
     * @return MorphTo<Model, self>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * All activation records (seats consumed) for this license.
     *
     * @return HasMany<Activation, self>
     */
    public function activations(): HasMany
    {
        /** @var class-string<Activation> $activationModel */
        $activationModel = config('license.activation_model', Activation::class);

        return $this->hasMany($activationModel, 'license_id');
    }

    // -------------------------------------------------------------------------
    // LicenseContract Accessors
    // -------------------------------------------------------------------------

    /**
     * Return the product name or tier this license is for.
     */
    public function getProduct(): string
    {
        return (string) $this->product;
    }

    /**
     * Return the maximum number of allowed activation seats.
     */
    public function getSeats(): int
    {
        return (int) $this->seats;
    }

    /**
     * Return the expiration timestamp, or null if the license never expires.
     */
    public function getExpiresAt(): ?\Carbon\Carbon
    {
        return $this->expires_at;
    }

    /**
     * Return the revocation timestamp, or null if the license is not revoked.
     */
    public function getRevokedAt(): ?\Carbon\Carbon
    {
        return $this->revoked_at;
    }

    /**
     * Return the total number of active activation records for this license.
     */
    public function countActivations(): int
    {
        return $this->activations()->count();
    }

    // -------------------------------------------------------------------------
    // Status Checks
    // -------------------------------------------------------------------------

    /**
     * Determine whether the license is currently valid (not revoked, not
     * fully expired beyond its grace window).
     */
    public function isValid(): bool
    {
        if ($this->isRevoked()) {
            return false;
        }

        if ($this->isExpired() && ! $this->isInGracePeriod()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the license has passed its expiration date.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Determine whether the license is expired but still within the
     * configured grace window.
     */
    public function isInGracePeriod(): bool
    {
        if (! $this->isExpired()) {
            return false;
        }

        $graceDays = (int) config('license.grace_period_days', 0);

        if ($graceDays === 0) {
            return false;
        }

        return $this->expires_at->copy()->addDays($graceDays)->isFuture();
    }

    /**
     * Determine whether the license has been revoked.
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    // -------------------------------------------------------------------------
    // Seat Calculations
    // -------------------------------------------------------------------------

    /**
     * Return the number of activation slots still available.
     */
    public function seatsRemaining(): int
    {
        $used = $this->activations()->count();

        return max(0, $this->seats - $used);
    }

    /**
     * Determine whether at least one seat is available.
     */
    public function hasAvailableSeat(): bool
    {
        return $this->seatsRemaining() > 0;
    }

    // -------------------------------------------------------------------------
    // Grace Period Helpers
    // -------------------------------------------------------------------------

    /**
     * Return the number of days remaining in the grace period, or 0.
     */
    public function graceDaysRemaining(): int
    {
        if (! $this->isInGracePeriod()) {
            return 0;
        }

        $graceDays = (int) config('license.grace_period_days', 0);
        $graceEnd  = $this->expires_at->copy()->addDays($graceDays);

        return (int) now()->diffInDays($graceEnd, false);
    }
}
