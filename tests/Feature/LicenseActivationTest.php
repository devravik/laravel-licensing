<?php

namespace DevRavik\LaravelLicensing\Tests\Feature;

use DevRavik\LaravelLicensing\Exceptions\LicenseAlreadyActivatedException;
use DevRavik\LaravelLicensing\Exceptions\SeatLimitExceededException;
use DevRavik\LaravelLicensing\Facades\License;
use DevRavik\LaravelLicensing\Tests\TestCase;

class LicenseActivationTest extends TestCase
{
    public function test_license_can_be_activated(): void
    {
        $user    = $this->createUser();
        $license = License::for($user)->product('pro')->seats(2)->create();
        $key     = $license->key;

        $activation = License::activate($key, 'app.example.com');

        $this->assertNotNull($activation->id);
        $this->assertDatabaseHas('license_activations', [
            'binding' => 'app.example.com',
        ]);
    }

    public function test_seat_limit_is_enforced(): void
    {
        $user    = $this->createUser();
        $license = License::for($user)->product('pro')->seats(2)->create();
        $key     = $license->key;

        License::activate($key, 'domain-1.com');
        License::activate($key, 'domain-2.com');

        $this->expectException(SeatLimitExceededException::class);
        License::activate($key, 'domain-3.com');
    }

    public function test_duplicate_binding_throws_exception(): void
    {
        $user    = $this->createUser();
        $license = License::for($user)->product('pro')->seats(5)->create();
        $key     = $license->key;

        License::activate($key, 'domain-1.com');

        $this->expectException(LicenseAlreadyActivatedException::class);
        License::activate($key, 'domain-1.com');
    }

    public function test_deactivation_frees_a_seat(): void
    {
        $user    = $this->createUser();
        $license = License::for($user)->product('pro')->seats(1)->create();
        $key     = $license->key;

        License::activate($key, 'domain-1.com');
        License::deactivate($key, 'domain-1.com');
        License::activate($key, 'domain-2.com');

        $this->assertDatabaseHas('license_activations', ['binding' => 'domain-2.com']);
        $this->assertDatabaseMissing('license_activations', ['binding' => 'domain-1.com']);
    }

    public function test_seats_remaining_is_calculated_correctly(): void
    {
        $user    = $this->createUser();
        $license = License::for($user)->product('pro')->seats(3)->create();
        $key     = $license->key;

        $this->assertSame(3, $license->fresh()->seatsRemaining());

        License::activate($key, 'domain-1.com');
        $this->assertSame(2, $license->fresh()->seatsRemaining());

        License::activate($key, 'domain-2.com');
        $this->assertSame(1, $license->fresh()->seatsRemaining());
    }

    public function test_deactivate_returns_false_for_unknown_binding(): void
    {
        $user    = $this->createUser();
        $license = License::for($user)->product('pro')->seats(2)->create();
        $key     = $license->key;

        $result = License::deactivate($key, 'non-existent.com');

        $this->assertFalse($result);
    }

    public function test_activation_stores_binding_in_database(): void
    {
        $user    = $this->createUser();
        $license = License::for($user)->product('basic')->seats(3)->create();

        License::activate($license->key, 'site-a.com');
        License::activate($license->key, 'site-b.com');

        $this->assertDatabaseCount('license_activations', 2);
    }
}
