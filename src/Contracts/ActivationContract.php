<?php

namespace DevRavik\LaravelLicensing\Contracts;

/**
 * Defines the required API for an activation (seat binding) model.
 *
 * Any class implementing this contract can be used as the activation_model
 * in config/license.php without modifying any package internals.
 */
interface ActivationContract
{
    /**
     * Return the binding identifier for this activation.
     *
     * Examples: 'app.example.com', '203.0.113.42', 'hw-uuid-abc123'
     *
     * @return string
     */
    public function getBinding(): string;

    /**
     * Return the primary key of the license this activation belongs to.
     *
     * @return int
     */
    public function getLicenseId(): int;
}
