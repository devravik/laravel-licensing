<?php

namespace DevRavik\LaravelLicensing\Tests\Feature;

use Carbon\Carbon;
use DevRavik\LaravelLicensing\Exceptions\InvalidLicenseException;
use DevRavik\LaravelLicensing\Exceptions\LicenseExpiredException;
use DevRavik\LaravelLicensing\Exceptions\LicenseRevokedException;
use DevRavik\LaravelLicensing\Facades\License;
use DevRavik\LaravelLicensing\Tests\TestCase;

class LicenseValidationTest extends TestCase
{
    public function test_valid_license_passes_validation(): void
    {
        $user    = $this->createUser();
        $license = License::for($user)->product('pro')->create();
        $rawKey  = $license->key;

        $validated = License::validate($rawKey);

        $this->assertTrue($validated->isValid());
        $this->assertFalse($validated->isExpired());
        $this->assertFalse($validated->isRevoked());
    }

    public function test_invalid_key_throws_exception(): void
    {
        $this->expectException(InvalidLicenseException::class);
        License::validate('completely-invalid-key-that-does-not-exist');
    }

    public function test_expired_license_throws_exception(): void
    {
        config()->set('license.grace_period_days', 0);

        $user    = $this->createUser();
        $license = License::for($user)->product('pro')->expiresInDays(1)->create();
        $rawKey  = $license->key;

        // Manually push expires_at into the past.
        $license->fresh()->update(['expires_at' => Carbon::yesterday()]);

        $this->expectException(LicenseExpiredException::class);
        License::validate($rawKey);
    }

    public function test_license_in_grace_period_passes_validation(): void
    {
        config()->set('license.grace_period_days', 7);

        $user    = $this->createUser();
        $license = License::for($user)->product('pro')->expiresInDays(1)->create();
        $rawKey  = $license->key;

        // Manually push expires_at 3 days into the past (within the 7-day grace period).
        $license->fresh()->update(['expires_at' => now()->subDays(3)]);

        $validated = License::validate($rawKey);

        $this->assertTrue($validated->isInGracePeriod());
    }

    public function test_revoked_license_fails_validation(): void
    {
        $user    = $this->createUser();
        $license = License::for($user)->product('pro')->create();
        $rawKey  = $license->key;

        License::revoke($rawKey);

        $this->expectException(LicenseRevokedException::class);
        License::validate($rawKey);
    }

    public function test_validation_returns_license_contract(): void
    {
        $user    = $this->createUser();
        $license = License::for($user)->product('enterprise')->create();

        $validated = License::validate($license->key);

        $this->assertSame('enterprise', $validated->product);
    }
}
