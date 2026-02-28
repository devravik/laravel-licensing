<?php

namespace DevRavik\LaravelLicensing\Contracts;

use DevRavik\LaravelLicensing\Services\LicenseBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Defines the primary API for all license management operations.
 *
 * This is the contract resolved when the License facade is used:
 *   License::for($user)->product('pro')->create();
 *   License::validate($key);
 */
interface LicenseManagerContract
{
    /**
     * Begin building a new license for the given owner model.
     *
     * @param  Model  $owner  Any Eloquent model (User, Team, etc.)
     */
    public function for(Model $owner): LicenseBuilder;

    /**
     * Validate a license key and return the corresponding license instance.
     *
     * Throws an exception if the key is invalid, expired, or revoked.
     *
     * @param  string  $key  The raw (unhashed) license key.
     *
     * @throws \DevRavik\LaravelLicensing\Exceptions\InvalidLicenseException
     * @throws \DevRavik\LaravelLicensing\Exceptions\LicenseExpiredException
     * @throws \DevRavik\LaravelLicensing\Exceptions\LicenseRevokedException
     */
    public function validate(string $key): LicenseContract;

    /**
     * Activate a license against an identifier (domain, IP, machine ID).
     *
     * @param  string  $key  The raw license key.
     * @param  string  $binding  The identifier to bind the activation to.
     *
     * @throws \DevRavik\LaravelLicensing\Exceptions\InvalidLicenseException
     * @throws \DevRavik\LaravelLicensing\Exceptions\SeatLimitExceededException
     * @throws \DevRavik\LaravelLicensing\Exceptions\LicenseAlreadyActivatedException
     */
    public function activate(string $key, string $binding): ActivationContract;

    /**
     * Remove an activation binding from a license, freeing the seat.
     *
     * @param  string  $key  The raw license key.
     * @param  string  $binding  The binding identifier to remove.
     * @return bool True on success, false if binding was not found.
     *
     * @throws \DevRavik\LaravelLicensing\Exceptions\InvalidLicenseException
     */
    public function deactivate(string $key, string $binding): bool;

    /**
     * Permanently revoke a license.
     *
     * All subsequent validate() or activate() calls on this key will fail.
     *
     * @param  string  $key  The raw license key.
     *
     * @throws \DevRavik\LaravelLicensing\Exceptions\InvalidLicenseException
     */
    public function revoke(string $key): bool;

    /**
     * Find a license by its raw key without performing any validation.
     *
     * Returns null if the key does not match any record.
     *
     * @param  string  $key  The raw license key.
     */
    public function find(string $key): ?LicenseContract;

    /**
     * List licenses with optional filtering.
     *
     * @param  array<string, mixed>  $filters  Filter options (product, owner_type, owner_id, status, expired, revoked, per_page)
     * @param  int  $perPage  Number of licenses per page (default: 15)
     * @return Collection<int, LicenseContract>|LengthAwarePaginator<int, LicenseContract>
     */
    public function list(array $filters = [], int $perPage = 15): Collection|LengthAwarePaginator;

    /**
     * Get license and activation statistics.
     *
     * @param  string|null  $product  Filter statistics by product
     * @return array<string, int>
     */
    public function getStatistics(?string $product = null): array;
}
