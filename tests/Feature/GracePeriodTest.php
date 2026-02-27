<?php

namespace DevRavik\LaravelLicensing\Tests\Feature;

use DevRavik\LaravelLicensing\Exceptions\LicenseExpiredException;
use DevRavik\LaravelLicensing\Facades\License;
use DevRavik\LaravelLicensing\Tests\TestCase;

class GracePeriodTest extends TestCase
{
    public function test_license_in_grace_period_is_valid(): void
    {
        config()->set('license.grace_period_days', 7);

        $user    = $this->createUser();
        $license = License::for($user)->product('pro')->expiresInDays(1)->create();
        $rawKey  = $license->key;

        // Expire 3 days ago — still within 7-day grace window.
        $license->fresh()->update(['expires_at' => now()->subDays(3)]);

        $validated = License::validate($rawKey);

        $this->assertTrue($validated->isInGracePeriod());
        $this->assertTrue($validated->isExpired());
        $this->assertTrue($validated->isValid());
    }

    public function test_license_outside_grace_period_throws(): void
    {
        config()->set('license.grace_period_days', 3);

        $user    = $this->createUser();
        $license = License::for($user)->product('pro')->expiresInDays(1)->create();
        $rawKey  = $license->key;

        // Expire 10 days ago — past the 3-day grace window.
        $license->fresh()->update(['expires_at' => now()->subDays(10)]);

        $this->expectException(LicenseExpiredException::class);
        License::validate($rawKey);
    }

    public function test_grace_period_zero_means_no_grace(): void
    {
        config()->set('license.grace_period_days', 0);

        $user    = $this->createUser();
        $license = License::for($user)->product('pro')->expiresInDays(1)->create();
        $rawKey  = $license->key;

        $license->fresh()->update(['expires_at' => now()->subDay()]);

        $this->expectException(LicenseExpiredException::class);
        License::validate($rawKey);
    }

    public function test_non_expired_license_is_never_in_grace_period(): void
    {
        config()->set('license.grace_period_days', 7);

        $user    = $this->createUser();
        $license = License::for($user)->product('pro')->expiresInDays(30)->create();

        $fresh = $license->fresh();

        $this->assertFalse($fresh->isExpired());
        $this->assertFalse($fresh->isInGracePeriod());
    }

    public function test_grace_days_remaining_returns_correct_value(): void
    {
        config()->set('license.grace_period_days', 7);

        $user    = $this->createUser();
        $license = License::for($user)->product('pro')->expiresInDays(1)->create();

        // Expire 2 days ago → 5 grace days remaining.
        $license->fresh()->update(['expires_at' => now()->subDays(2)]);

        $daysRemaining = $license->fresh()->graceDaysRemaining();

        $this->assertGreaterThanOrEqual(4, $daysRemaining);
        $this->assertLessThanOrEqual(5, $daysRemaining);
    }

    public function test_license_without_expiry_is_never_in_grace_period(): void
    {
        config()->set('license.grace_period_days', 7);
        config()->set('license.default_expiry_days', null);

        $user    = $this->createUser();
        $license = License::for($user)->product('pro')->create();

        $fresh = $license->fresh();

        $this->assertFalse($fresh->isExpired());
        $this->assertFalse($fresh->isInGracePeriod());
        $this->assertSame(0, $fresh->graceDaysRemaining());
    }
}
