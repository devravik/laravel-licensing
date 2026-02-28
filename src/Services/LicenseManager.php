<?php

namespace DevRavik\LaravelLicensing\Services;

use Carbon\Carbon;
use DevRavik\LaravelLicensing\Contracts\ActivationContract;
use DevRavik\LaravelLicensing\Contracts\KeyGeneratorContract;
use DevRavik\LaravelLicensing\Contracts\LicenseContract;
use DevRavik\LaravelLicensing\Contracts\LicenseGeneratorInterface;
use DevRavik\LaravelLicensing\Contracts\LicenseManagerContract;
use DevRavik\LaravelLicensing\Events\LicenseActivated;
use DevRavik\LaravelLicensing\Events\LicenseCreated;
use DevRavik\LaravelLicensing\Events\LicenseDeactivated;
use DevRavik\LaravelLicensing\Events\LicenseRevoked;
use DevRavik\LaravelLicensing\Exceptions\InvalidLicenseException;
use DevRavik\LaravelLicensing\Exceptions\InvalidSignatureException;
use DevRavik\LaravelLicensing\Exceptions\LicenseAlreadyActivatedException;
use DevRavik\LaravelLicensing\Exceptions\LicenseExpiredException;
use DevRavik\LaravelLicensing\Exceptions\LicenseRevokedException;
use DevRavik\LaravelLicensing\Exceptions\SeatLimitExceededException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Model;

class LicenseManager implements LicenseManagerContract
{
    public function __construct(
        protected KeyGeneratorContract $keyGenerator,
        protected LicenseGeneratorInterface $licenseGenerator,
        protected Hasher $hasher,
        protected Dispatcher $events,
        protected LicenseValidator $validator,
        protected SignatureVerifier $signatureVerifier,
    ) {}

    // -------------------------------------------------------------------------
    // Builder Entry Point
    // -------------------------------------------------------------------------

    /**
     * Begin building a new license for the given owner model.
     */
    public function for(Model $owner): LicenseBuilder
    {
        return new LicenseBuilder($owner, $this);
    }

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    /**
     * Persist a new license record and return the model with the raw key set.
     *
     * Called internally by LicenseBuilder::create(). Not exposed on the facade
     * directly — consumers always go through the builder.
     *
     * @internal
     */
    public function createLicense(
        Model $owner,
        string $product,
        int $seats,
        ?Carbon $expiresAt,
    ): LicenseContract {
        // Build payload for license generator
        $payload = [
            'product' => $product,
            'seats' => $seats,
            'expires_at' => $expiresAt?->toIso8601String(),
            'owner_type' => get_class($owner),
            'owner_id' => $owner->getKey(),
            'created_at' => now()->toIso8601String(),
        ];

        // Generate the license key using the configured strategy
        $rawKey = $this->licenseGenerator->generate($payload);

        $licenseModelClass = config('license.license_model');

        /** @var \DevRavik\LaravelLicensing\Models\License $license */
        $license = new $licenseModelClass;
        $license->product = $product;
        $license->seats = $seats;
        $license->expires_at = $expiresAt;
        $license->revoked_at = null;

        // Associate the polymorphic owner.
        $license->owner()->associate($owner);

        // Hash the key before persisting if hash_keys is enabled.
        // Also store a SHA-256 lookup token so that findByKey() can pre-filter
        // by an indexed column instead of doing a full-table bcrypt scan.
        $shouldHash = (bool) config('license.hash_keys', true);
        $license->key = $shouldHash ? $this->hasher->make($rawKey) : $rawKey;
        $license->lookup_token = $shouldHash ? hash('sha256', $rawKey) : null;

        $license->save();

        // Temporarily overwrite the key attribute with the raw value so the
        // caller can read it once. This value is NOT persisted.
        $license->key = $rawKey;

        $this->events->dispatch(new LicenseCreated($license));

        return $license;
    }

    // -------------------------------------------------------------------------
    // Validate
    // -------------------------------------------------------------------------

    /**
     * Validate a raw license key and return the license if valid.
     *
     * @throws InvalidLicenseException If the key does not match any record.
     * @throws InvalidSignatureException If signature verification fails (signed mode only).
     * @throws LicenseRevokedException If the license has been revoked.
     * @throws LicenseExpiredException If the license is expired beyond grace.
     */
    public function validate(string $key): LicenseContract
    {
        $license = $this->findByKey($key);

        if ($license === null) {
            throw InvalidLicenseException::forKey($key);
        }

        // Delegate revoked/expired state checks to LicenseValidator.
        $this->validator->assertValid($license);

        return $license;
    }

    // -------------------------------------------------------------------------
    // Activate
    // -------------------------------------------------------------------------

    /**
     * Activate a license against a binding identifier, consuming one seat.
     *
     * @throws InvalidLicenseException If the key is not valid.
     * @throws LicenseRevokedException If the license is revoked.
     * @throws LicenseExpiredException If the license is expired.
     * @throws SeatLimitExceededException If no seats remain.
     * @throws LicenseAlreadyActivatedException If the binding already exists.
     */
    public function activate(string $key, string $binding): ActivationContract
    {
        // Validate first — throws on invalid/expired/revoked.
        /** @var \DevRavik\LaravelLicensing\Models\License $license */
        $license = $this->validate($key);

        $activationModelClass = config('license.activation_model');

        // Check for duplicate binding.
        $existing = $license->activations()
            ->where('binding', $binding)
            ->first();

        if ($existing !== null) {
            throw LicenseAlreadyActivatedException::forBinding($license, $binding);
        }

        // Check seat availability.
        if (! $license->hasAvailableSeat()) {
            throw SeatLimitExceededException::forLicense($license);
        }

        return $this->createActivation($license, $binding);
    }

    /**
     * Create an activation for a license (internal helper).
     *
     * @param  LicenseContract  $license  The license instance
     * @param  string  $binding  The binding identifier
     * @return ActivationContract
     *
     * @throws LicenseAlreadyActivatedException If the binding already exists.
     * @throws SeatLimitExceededException If no seats remain.
     */
    protected function createActivation(LicenseContract $license, string $binding): ActivationContract
    {
        $activationModelClass = config('license.activation_model');

        // Check for duplicate binding.
        $existing = $license->activations()
            ->where('binding', $binding)
            ->first();

        if ($existing !== null) {
            throw LicenseAlreadyActivatedException::forBinding($license, $binding);
        }

        // Check seat availability.
        if (! $license->hasAvailableSeat()) {
            throw SeatLimitExceededException::forLicense($license);
        }

        /** @var \DevRavik\LaravelLicensing\Models\Activation $activation */
        $activation = new $activationModelClass;
        $activation->license_id = $license->getKey();
        $activation->binding = $binding;
        $activation->activated_at = now();
        $activation->save();

        $this->events->dispatch(new LicenseActivated($license, $activation));

        return $activation;
    }

    // -------------------------------------------------------------------------
    // Deactivate
    // -------------------------------------------------------------------------

    /**
     * Remove an activation binding from a license, freeing the seat.
     *
     * Returns true if the binding was found and deleted, false otherwise.
     *
     * @throws InvalidLicenseException If the key is not found.
     */
    public function deactivate(string $key, string $binding): bool
    {
        /** @var \DevRavik\LaravelLicensing\Models\License|null $license */
        $license = $this->findByKey($key);

        if ($license === null) {
            throw InvalidLicenseException::forKey($key);
        }

        $activation = $license->activations()
            ->where('binding', $binding)
            ->first();

        if ($activation === null) {
            return false;
        }

        $activation->delete();

        $this->events->dispatch(new LicenseDeactivated($license, $binding));

        return true;
    }

    // -------------------------------------------------------------------------
    // Revoke
    // -------------------------------------------------------------------------

    /**
     * Permanently revoke a license by setting its revoked_at timestamp.
     *
     * @throws InvalidLicenseException If the key is not found.
     */
    public function revoke(string $key): bool
    {
        /** @var \DevRavik\LaravelLicensing\Models\License|null $license */
        $license = $this->findByKey($key);

        if ($license === null) {
            throw InvalidLicenseException::forKey($key);
        }

        $license->revoked_at = now();
        $license->save();

        $this->events->dispatch(new LicenseRevoked($license));

        return true;
    }

    // -------------------------------------------------------------------------
    // Find (no validation)
    // -------------------------------------------------------------------------

    /**
     * Find a license by raw key without throwing on failure.
     *
     * Returns null if no matching license is found.
     */
    public function find(string $key): ?LicenseContract
    {
        return $this->findByKey($key);
    }

    // -------------------------------------------------------------------------
    // Internal Key Lookup
    // -------------------------------------------------------------------------

    /**
     * Look up a license record by raw key.
     *
     * When using signed generation: verifies the signature first before lookup.
     *
     * When hash_keys is false: performs a direct DB equality check (O(log n)
     * via the key index).
     *
     * When hash_keys is true: computes a SHA-256 digest of the raw key and
     * uses it to filter by the indexed lookup_token column, then confirms
     * authenticity with Hash::check() (bcrypt). Because SHA-256 collisions
     * across license keys are astronomically unlikely, the bcrypt check
     * effectively always runs on at most one candidate row — O(log n) in
     * practice rather than the previous O(n) full-table cursor scan.
     *
     * @return \DevRavik\LaravelLicensing\Models\License|null
     *
     * @throws InvalidSignatureException If signature verification fails (signed mode only).
     */
    protected function findByKey(string $rawKey): ?LicenseContract
    {
        $generationStrategy = config('license.license_generation', 'random');

        // If using signed generation, verify signature first
        if ($generationStrategy === 'signed') {
            try {
                $this->signatureVerifier->verify($rawKey);
            } catch (InvalidSignatureException $e) {
                // Re-throw signature exceptions
                throw $e;
            } catch (\Exception $e) {
                // Wrap other exceptions (e.g., missing keys, invalid format)
                throw InvalidSignatureException::verificationFailed($e->getMessage());
            }
        }

        $licenseModelClass = config('license.license_model');
        $shouldHash = (bool) config('license.hash_keys', true);

        if (! $shouldHash) {
            return $licenseModelClass::where('key', $rawKey)->first();
        }

        // Use the SHA-256 lookup token to narrow the candidate set to O(1)
        // rows before paying the cost of bcrypt comparison.
        $lookupToken = hash('sha256', $rawKey);

        foreach ($licenseModelClass::where('lookup_token', $lookupToken)->cursor() as $license) {
            if ($this->hasher->check($rawKey, $license->key)) {
                return $license;
            }
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // List
    // -------------------------------------------------------------------------

    /**
     * List licenses with optional filtering.
     *
     * @param  array<string, mixed>  $filters  Filter options (product, owner_type, owner_id, status, expired, revoked)
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(array $filters = []): \Illuminate\Database\Eloquent\Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $licenseModelClass = config('license.license_model');
        $query = $licenseModelClass::query();

        // Filter by product
        if (isset($filters['product']) && $filters['product'] !== null) {
            $query->where('product', $filters['product']);
        }

        // Filter by owner type
        if (isset($filters['owner_type']) && $filters['owner_type'] !== null) {
            $query->where('owner_type', $filters['owner_type']);
        }

        // Filter by owner ID
        if (isset($filters['owner_id']) && $filters['owner_id'] !== null) {
            $query->where('owner_id', $filters['owner_id']);
        }

        // Filter by status
        if (isset($filters['status'])) {
            match ($filters['status']) {
                'active' => $query->whereNull('revoked_at')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    }),
                'expired' => $query->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now())
                    ->whereNull('revoked_at'),
                'revoked' => $query->whereNotNull('revoked_at'),
                default => null,
            };
        }

        // Filter expired licenses
        if (isset($filters['expired']) && $filters['expired'] === true) {
            $query->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->whereNull('revoked_at');
        }

        // Filter revoked licenses
        if (isset($filters['revoked']) && $filters['revoked'] === true) {
            $query->whereNotNull('revoked_at');
        }

        // Order by created_at descending
        $query->orderBy('created_at', 'desc');

        // Paginate if requested
        if (isset($filters['per_page']) && $filters['per_page'] > 0) {
            return $query->paginate((int) $filters['per_page']);
        }

        return $query->get();
    }

    // -------------------------------------------------------------------------
    // Statistics
    // -------------------------------------------------------------------------

    /**
     * Get license statistics.
     *
     * @param  string|null  $product  Optional product filter
     * @return array<string, mixed>  Statistics array
     */
    public function getStatistics(?string $product = null): array
    {
        $licenseModelClass = config('license.license_model');
        $activationModelClass = config('license.activation_model');

        $licenseQuery = $licenseModelClass::query();
        if ($product !== null) {
            $licenseQuery->where('product', $product);
        }

        $totalLicenses = $licenseQuery->count();
        $activeLicenses = (clone $licenseQuery)
            ->whereNull('revoked_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();
        $expiredLicenses = (clone $licenseQuery)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereNull('revoked_at')
            ->count();
        $revokedLicenses = (clone $licenseQuery)
            ->whereNotNull('revoked_at')
            ->count();

        // Calculate total seats
        $totalSeats = (clone $licenseQuery)->sum('seats');

        // Calculate used seats (total activations)
        $usedSeats = $activationModelClass::query()
            ->when($product !== null, function ($q) use ($product, $licenseModelClass) {
                $licenseIds = $licenseModelClass::where('product', $product)->pluck('id');
                $q->whereIn('license_id', $licenseIds);
            })
            ->count();
        $availableSeats = max(0, $totalSeats - $usedSeats);

        // Total activations
        $totalActivations = $usedSeats;

        return [
            'total_licenses' => $totalLicenses,
            'active_licenses' => $activeLicenses,
            'expired_licenses' => $expiredLicenses,
            'revoked_licenses' => $revokedLicenses,
            'total_seats' => $totalSeats,
            'used_seats' => $usedSeats,
            'available_seats' => $availableSeats,
            'total_activations' => $totalActivations,
            'product' => $product,
        ];
    }
}
