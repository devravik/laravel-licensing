<?php

namespace DevRavik\LaravelLicensing\Tests\Feature;

use DevRavik\LaravelLicensing\Facades\License;
use DevRavik\LaravelLicensing\Tests\TestCase;

class LicenseCreationTest extends TestCase
{
    public function test_license_can_be_created_for_a_user(): void
    {
        $user = $this->createUser();

        $license = License::for($user)
            ->product('pro')
            ->seats(3)
            ->expiresInDays(30)
            ->create();

        $this->assertNotNull($license->key);
        $this->assertSame('pro', $license->product);
        $this->assertSame(3, $license->seats);
        $this->assertDatabaseHas('licenses', [
            'product' => 'pro',
            'owner_id' => $user->id,
            'owner_type' => get_class($user),
        ]);
    }

    public function test_raw_key_is_returned_at_creation_time(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->create();
        $rawKey = $license->key;

        $this->assertNotEmpty($rawKey);
        $this->assertSame(32, strlen($rawKey)); // default key_length
    }

    public function test_raw_key_is_not_stored_in_database(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->create();
        $rawKey = $license->key;

        $this->assertDatabaseMissing('licenses', ['key' => $rawKey]);
    }

    public function test_license_without_expiry_uses_config_default(): void
    {
        config()->set('license.default_expiry_days', 90);

        $user = $this->createUser();
        $license = License::for($user)->product('pro')->create();

        $this->assertNotNull($license->fresh()->expires_at);
        $this->assertEqualsWithDelta(
            now()->addDays(90)->timestamp,
            $license->fresh()->expires_at->timestamp,
            60 // within 60 seconds
        );
    }

    public function test_product_is_required_before_create(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = $this->createUser();
        License::for($user)->create();
    }

    public function test_license_is_persisted_to_database(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('basic')->seats(1)->create();

        $this->assertDatabaseCount('licenses', 1);
        $this->assertNotNull($license->id);
    }

    public function test_license_expiry_can_be_set_explicitly(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->expiresInDays(15)->create();

        $this->assertEqualsWithDelta(
            now()->addDays(15)->timestamp,
            $license->fresh()->expires_at->timestamp,
            60
        );
    }

    public function test_multiple_licenses_can_be_created_for_same_user(): void
    {
        $user = $this->createUser();

        License::for($user)->product('basic')->create();
        License::for($user)->product('pro')->create();

        $this->assertDatabaseCount('licenses', 2);
    }
}
