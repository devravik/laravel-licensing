<?php

namespace DevRavik\LaravelLicensing\Models;

use DevRavik\LaravelLicensing\Contracts\ActivationContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int             $id
 * @property int             $license_id
 * @property string          $binding
 * @property \Carbon\Carbon  $activated_at
 * @property \Carbon\Carbon  $created_at
 * @property \Carbon\Carbon  $updated_at
 */
class Activation extends Model implements ActivationContract
{
    /**
     * The table associated with the model.
     */
    protected $table = 'license_activations';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'license_id',
        'binding',
        'activated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'activated_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The license that this activation belongs to.
     *
     * @return BelongsTo<License, self>
     */
    public function license(): BelongsTo
    {
        /** @var class-string<License> $licenseModel */
        $licenseModel = config('license.license_model', License::class);

        return $this->belongsTo($licenseModel, 'license_id');
    }

    // -------------------------------------------------------------------------
    // ActivationContract Implementation
    // -------------------------------------------------------------------------

    /**
     * Return the binding identifier for this activation.
     *
     * Provides a stable API surface even if the underlying column name changes.
     */
    public function getBinding(): string
    {
        return $this->binding;
    }

    /**
     * Return the primary key of the license this activation belongs to.
     *
     * Provides a stable API surface even if the underlying column name changes.
     */
    public function getLicenseId(): int
    {
        return (int) $this->license_id;
    }
}
