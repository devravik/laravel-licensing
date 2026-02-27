<?php

namespace DevRavik\LaravelLicensing\Tests\Feature;

use DevRavik\LaravelLicensing\Facades\License;
use DevRavik\LaravelLicensing\Support\HasLicenses;
use DevRavik\LaravelLicensing\Tests\TestCase;

/**
 * Tests for the HasLicenses trait.
 *
 * We attach the trait to a temporary sub-class of the Testbench User model
 * so that we exercise the real polymorphic queries without modifying the
 * Testbench base.
 */
class HasLicensesTest extends TestCase
{
    // -------------------------------------------------------------------------
    // licenses() relationship
    // -------------------------------------------------------------------------

    public function test_licenses_relationship_returns_all_licenses_for_owner(): void
    {
        $user = $this->createUserWithTrait();

        License::for($user)->product('basic')->create();
        License::for($user)->product('pro')->create();

        $this->assertCount(2, $user->licenses);
    }

    public function test_licenses_relationship_is_scoped_to_the_owner(): void
    {
        $userA = $this->createUserWithTrait();
        $userB = $this->createUserWithTrait();

        License::for($userA)->product('pro')->create();
        License::for($userB)->product('basic')->create();

        $this->assertCount(1, $userA->licenses);
        $this->assertCount(1, $userB->licenses);
    }

    // -------------------------------------------------------------------------
    // activeLicenses()
    // -------------------------------------------------------------------------

    public function test_active_licenses_returns_non_revoked_licenses(): void
    {
        $user = $this->createUserWithTrait();

        $active = License::for($user)->product('pro')->create();
        $revoked = License::for($user)->product('basic')->create();

        License::revoke($revoked->key);

        $active = $user->activeLicenses();

        $this->assertCount(1, $active);
        $this->assertSame('pro', $active->first()->product);
    }

    public function test_active_licenses_excludes_licenses_expired_beyond_grace_period(): void
    {
        config()->set('license.grace_period_days', 0);

        $user = $this->createUserWithTrait();

        $license = License::for($user)->product('pro')->expiresInDays(1)->create();

        // Expire it yesterday.
        $license->fresh()->update(['expires_at' => now()->subDay()]);

        $this->assertCount(0, $user->activeLicenses());
    }

    public function test_active_licenses_includes_licenses_within_grace_period(): void
    {
        config()->set('license.grace_period_days', 7);

        $user = $this->createUserWithTrait();

        $license = License::for($user)->product('pro')->expiresInDays(1)->create();

        // Expire 3 days ago — still within the 7-day grace window.
        $license->fresh()->update(['expires_at' => now()->subDays(3)]);

        $this->assertCount(1, $user->activeLicenses());
    }

    public function test_active_licenses_includes_never_expiring_licenses(): void
    {
        config()->set('license.default_expiry_days', null);

        $user = $this->createUserWithTrait();

        License::for($user)->product('pro')->create();

        $this->assertCount(1, $user->activeLicenses());
    }

    public function test_active_licenses_excludes_licenses_just_outside_grace_period(): void
    {
        config()->set('license.grace_period_days', 3);

        $user = $this->createUserWithTrait();

        $license = License::for($user)->product('pro')->expiresInDays(1)->create();

        // Expire 10 days ago — past the 3-day grace window.
        $license->fresh()->update(['expires_at' => now()->subDays(10)]);

        $this->assertCount(0, $user->activeLicenses());
    }

    public function test_active_licenses_returns_empty_collection_when_no_licenses(): void
    {
        $user = $this->createUserWithTrait();

        $this->assertCount(0, $user->activeLicenses());
    }

    // -------------------------------------------------------------------------
    // hasLicenseForProduct()
    // -------------------------------------------------------------------------

    public function test_has_license_for_product_returns_true_for_matching_product(): void
    {
        $user = $this->createUserWithTrait();

        License::for($user)->product('pro')->create();

        $this->assertTrue($user->hasLicenseForProduct('pro'));
    }

    public function test_has_license_for_product_returns_false_for_different_product(): void
    {
        $user = $this->createUserWithTrait();

        License::for($user)->product('basic')->create();

        $this->assertFalse($user->hasLicenseForProduct('pro'));
    }

    public function test_has_license_for_product_returns_false_for_revoked_license(): void
    {
        $user = $this->createUserWithTrait();

        $license = License::for($user)->product('pro')->create();
        License::revoke($license->key);

        $this->assertFalse($user->hasLicenseForProduct('pro'));
    }

    public function test_has_license_for_product_returns_false_when_expired_beyond_grace(): void
    {
        config()->set('license.grace_period_days', 0);

        $user = $this->createUserWithTrait();

        $license = License::for($user)->product('pro')->expiresInDays(1)->create();
        $license->fresh()->update(['expires_at' => now()->subDay()]);

        $this->assertFalse($user->hasLicenseForProduct('pro'));
    }

    public function test_has_license_for_product_returns_true_when_in_grace_period(): void
    {
        config()->set('license.grace_period_days', 7);

        $user = $this->createUserWithTrait();

        $license = License::for($user)->product('pro')->expiresInDays(1)->create();

        // Expire 3 days ago — within the 7-day grace window.
        $license->fresh()->update(['expires_at' => now()->subDays(3)]);

        $this->assertTrue($user->hasLicenseForProduct('pro'));
    }

    public function test_has_license_for_product_returns_false_when_no_licenses(): void
    {
        $user = $this->createUserWithTrait();

        $this->assertFalse($user->hasLicenseForProduct('pro'));
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /**
     * Create a Testbench User instance decorated with the HasLicenses trait.
     *
     * We create a real user in the DB and then cast it to an anonymous subclass
     * that mixes in HasLicenses, so the polymorphic queries work against the
     * real `users` table.
     */
    private function createUserWithTrait(): UserWithLicenses
    {
        $base = $this->createUser();

        // Hydrate an instance of the trait-bearing subclass from the DB row.
        return UserWithLicenses::find($base->id);
    }
}

/**
 * Concrete model used only by HasLicensesTest.
 * Extends the Laravel foundation User model (the same class the Testbench
 * UserFactory persists) and mixes in HasLicenses.
 */
class UserWithLicenses extends \Illuminate\Foundation\Auth\User
{
    use HasLicenses;

    protected $table = 'users';
}
