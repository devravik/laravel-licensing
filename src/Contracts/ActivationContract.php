<?php

namespace DevRavik\LaravelLicensing\Contracts;

use Carbon\Carbon;

/**
 * Defines the required API for an activation (seat binding) model.
 *
 * Any class implementing this contract can be used as the activation_model
 * in config/license.php without modifying any package internals.
 *
 * @property int $id
 * @property int $license_id
 * @property string $binding
 * @property Carbon $activated_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
interface ActivationContract
{
    /**
     * Return the binding identifier for this activation.
     *
     * Examples: 'app.example.com', '203.0.113.42', 'hw-uuid-abc123'
     */
    public function getBinding(): string;

    /**
     * Return the primary key of the license this activation belongs to.
     */
    public function getLicenseId(): int;
}
