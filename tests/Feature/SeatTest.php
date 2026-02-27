<?php

namespace DevRavik\LaravelLicensing\Tests\Feature;

use DevRavik\LaravelLicensing\Exceptions\SeatLimitExceededException;
use DevRavik\LaravelLicensing\Facades\License;
use DevRavik\LaravelLicensing\Tests\TestCase;

class SeatTest extends TestCase
{
    public function test_single_seat_license_allows_one_activation(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->seats(1)->create();

        License::activate($license->key, 'only-domain.com');

        $this->assertSame(0, $license->fresh()->seatsRemaining());
    }

    public function test_single_seat_license_rejects_second_activation(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->seats(1)->create();

        License::activate($license->key, 'domain-a.com');

        $this->expectException(SeatLimitExceededException::class);
        License::activate($license->key, 'domain-b.com');
    }

    public function test_seats_remaining_decrements_with_each_activation(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->seats(5)->create();
        $key = $license->key;

        $this->assertSame(5, $license->fresh()->seatsRemaining());

        for ($i = 1; $i <= 5; $i++) {
            License::activate($key, "domain-{$i}.com");
            $this->assertSame(5 - $i, $license->fresh()->seatsRemaining());
        }
    }

    public function test_has_available_seat_returns_true_when_seats_remain(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->seats(3)->create();

        $this->assertTrue($license->fresh()->hasAvailableSeat());
    }

    public function test_has_available_seat_returns_false_when_all_seats_used(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->seats(2)->create();

        License::activate($license->key, 'site-1.com');
        License::activate($license->key, 'site-2.com');

        $this->assertFalse($license->fresh()->hasAvailableSeat());
    }

    public function test_deactivation_increments_seats_remaining(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->seats(2)->create();
        $key = $license->key;

        License::activate($key, 'site-a.com');
        License::activate($key, 'site-b.com');

        $this->assertSame(0, $license->fresh()->seatsRemaining());

        License::deactivate($key, 'site-a.com');

        $this->assertSame(1, $license->fresh()->seatsRemaining());
    }

    public function test_seat_limit_exceeded_exception_carries_seat_counts(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->seats(2)->create();
        $key = $license->key;

        License::activate($key, 'site-1.com');
        License::activate($key, 'site-2.com');

        try {
            License::activate($key, 'site-3.com');
            $this->fail('Expected SeatLimitExceededException was not thrown.');
        } catch (SeatLimitExceededException $e) {
            $this->assertSame(2, $e->getSeatsAllowed());
            $this->assertSame(2, $e->getSeatsUsed());
            $this->assertSame(422, $e->getStatusCode());
        }
    }

    public function test_unlimited_seat_license_accepts_many_activations(): void
    {
        // seats > 100 effectively simulates unlimited for testing purposes.
        $user = $this->createUser();
        $license = License::for($user)->product('enterprise')->seats(100)->create();

        for ($i = 1; $i <= 10; $i++) {
            License::activate($license->key, "client-{$i}.com");
        }

        $this->assertSame(90, $license->fresh()->seatsRemaining());
    }
}
