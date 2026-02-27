<?php

namespace DevRavik\LaravelLicensing\Tests\Unit;

use DevRavik\LaravelLicensing\Contracts\LicenseContract;
use DevRavik\LaravelLicensing\Exceptions\LicenseExpiredException;
use DevRavik\LaravelLicensing\Exceptions\LicenseRevokedException;
use DevRavik\LaravelLicensing\Services\LicenseValidator;
use DevRavik\LaravelLicensing\Tests\TestCase;
use Mockery;

class LicenseValidatorTest extends TestCase
{
    private LicenseValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new LicenseValidator;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // assertValid()
    // -------------------------------------------------------------------------

    public function test_assert_valid_passes_for_active_non_expired_license(): void
    {
        $license = Mockery::mock(LicenseContract::class);
        $license->shouldReceive('isRevoked')->andReturn(false);
        $license->shouldReceive('isExpired')->andReturn(false);
        $license->shouldReceive('isInGracePeriod')->andReturn(false);

        // Should not throw.
        $this->validator->assertValid($license);

        $this->assertTrue(true); // Explicit pass assertion.
    }

    public function test_assert_valid_throws_for_revoked_license(): void
    {
        $license = Mockery::mock(LicenseContract::class);
        $license->shouldReceive('isRevoked')->andReturn(true);
        $license->shouldReceive('getRevokedAt')->andReturn(now());

        $this->expectException(LicenseRevokedException::class);
        $this->validator->assertValid($license);
    }

    public function test_assert_valid_throws_for_expired_license_outside_grace_period(): void
    {
        $license = Mockery::mock(LicenseContract::class);
        $license->shouldReceive('isRevoked')->andReturn(false);
        $license->shouldReceive('isExpired')->andReturn(true);
        $license->shouldReceive('isInGracePeriod')->andReturn(false);
        // LicenseExpiredException::forLicense() calls getExpiresAt() via the contract.
        $license->shouldReceive('getExpiresAt')->andReturn(now()->subDay());

        $this->expectException(LicenseExpiredException::class);
        $this->validator->assertValid($license);
    }

    public function test_assert_valid_passes_for_expired_license_within_grace_period(): void
    {
        $license = Mockery::mock(LicenseContract::class);
        $license->shouldReceive('isRevoked')->andReturn(false);
        $license->shouldReceive('isExpired')->andReturn(true);
        $license->shouldReceive('isInGracePeriod')->andReturn(true);

        // Should not throw — expired but in grace period.
        $this->validator->assertValid($license);

        $this->assertTrue(true);
    }

    public function test_revoked_check_takes_priority_over_expired_check(): void
    {
        $license = Mockery::mock(LicenseContract::class);
        $license->shouldReceive('isRevoked')->andReturn(true);
        $license->shouldReceive('getRevokedAt')->andReturn(now());

        // Even if expired, revoked should be thrown first.
        $this->expectException(LicenseRevokedException::class);
        $this->validator->assertValid($license);
    }

    // -------------------------------------------------------------------------
    // isValid()
    // -------------------------------------------------------------------------

    public function test_is_valid_returns_true_for_active_license(): void
    {
        $license = Mockery::mock(LicenseContract::class);
        $license->shouldReceive('isRevoked')->andReturn(false);
        $license->shouldReceive('isExpired')->andReturn(false);
        $license->shouldReceive('isInGracePeriod')->andReturn(false);

        $this->assertTrue($this->validator->isValid($license));
    }

    public function test_is_valid_returns_false_for_revoked_license(): void
    {
        $license = Mockery::mock(LicenseContract::class);
        $license->shouldReceive('isRevoked')->andReturn(true);
        $license->shouldReceive('getRevokedAt')->andReturn(now());

        $this->assertFalse($this->validator->isValid($license));
    }

    public function test_is_valid_returns_false_for_expired_license_outside_grace_period(): void
    {
        $license = Mockery::mock(LicenseContract::class);
        $license->shouldReceive('isRevoked')->andReturn(false);
        $license->shouldReceive('isExpired')->andReturn(true);
        $license->shouldReceive('isInGracePeriod')->andReturn(false);
        // LicenseExpiredException::forLicense() calls getExpiresAt() to build its message.
        $license->shouldReceive('getExpiresAt')->andReturn(now()->subDay());

        $this->assertFalse($this->validator->isValid($license));
    }

    public function test_is_valid_returns_true_for_license_in_grace_period(): void
    {
        $license = Mockery::mock(LicenseContract::class);
        $license->shouldReceive('isRevoked')->andReturn(false);
        $license->shouldReceive('isExpired')->andReturn(true);
        $license->shouldReceive('isInGracePeriod')->andReturn(true);

        $this->assertTrue($this->validator->isValid($license));
    }

    public function test_is_valid_does_not_throw_exceptions(): void
    {
        $license = Mockery::mock(LicenseContract::class);
        $license->shouldReceive('isRevoked')->andReturn(true);
        $license->shouldReceive('getRevokedAt')->andReturn(now());

        // isValid() must swallow exceptions and return false.
        $result = $this->validator->isValid($license);
        $this->assertFalse($result);
    }
}
