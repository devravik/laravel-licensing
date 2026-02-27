<?php

namespace DevRavik\LaravelLicensing\Tests\Feature;

use DevRavik\LaravelLicensing\Exceptions\InvalidLicenseException;
use DevRavik\LaravelLicensing\Exceptions\LicenseRevokedException;
use DevRavik\LaravelLicensing\Facades\License;
use DevRavik\LaravelLicensing\Tests\TestCase;

class LicenseRevocationTest extends TestCase
{
    public function test_license_can_be_revoked(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->create();
        $rawKey = $license->key;

        $result = License::revoke($rawKey);

        $this->assertTrue($result);
        $this->assertNotNull($license->fresh()->revoked_at);
    }

    public function test_revoked_license_is_marked_in_database(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->create();

        License::revoke($license->key);

        $dbRecord = $license->fresh();
        $this->assertTrue($dbRecord->isRevoked());
    }

    public function test_revoked_license_throws_on_validate(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->create();
        $rawKey = $license->key;

        License::revoke($rawKey);

        $this->expectException(LicenseRevokedException::class);
        License::validate($rawKey);
    }

    public function test_revoking_non_existent_key_throws_exception(): void
    {
        $this->expectException(InvalidLicenseException::class);
        License::revoke('not-a-real-key-at-all-abc123');
    }

    public function test_revoked_license_cannot_be_activated(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->seats(5)->create();
        $rawKey = $license->key;

        License::revoke($rawKey);

        $this->expectException(LicenseRevokedException::class);
        License::activate($rawKey, 'domain.com');
    }

    public function test_is_revoked_returns_false_for_active_license(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('basic')->create();

        $this->assertFalse($license->fresh()->isRevoked());
    }
}
