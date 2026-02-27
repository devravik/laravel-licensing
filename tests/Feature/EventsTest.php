<?php

namespace DevRavik\LaravelLicensing\Tests\Feature;

use DevRavik\LaravelLicensing\Events\LicenseActivated;
use DevRavik\LaravelLicensing\Events\LicenseCreated;
use DevRavik\LaravelLicensing\Events\LicenseDeactivated;
use DevRavik\LaravelLicensing\Events\LicenseRevoked;
use DevRavik\LaravelLicensing\Facades\License;
use DevRavik\LaravelLicensing\Tests\TestCase;
use Illuminate\Support\Facades\Event;

class EventsTest extends TestCase
{
    public function test_license_created_event_is_dispatched(): void
    {
        Event::fake([LicenseCreated::class]);

        $user    = $this->createUser();
        $license = License::for($user)->product('pro')->create();

        Event::assertDispatched(LicenseCreated::class, function ($event) use ($license) {
            return $event->license->product === 'pro';
        });
    }

    public function test_license_activated_event_is_dispatched(): void
    {
        Event::fake([LicenseCreated::class, LicenseActivated::class]);

        $user    = $this->createUser();
        $license = License::for($user)->product('pro')->create();
        $key     = $license->key;

        License::activate($key, 'example.com');

        Event::assertDispatched(LicenseActivated::class, function ($event) {
            return $event->activation->getBinding() === 'example.com';
        });
    }

    public function test_license_deactivated_event_is_dispatched(): void
    {
        Event::fake([LicenseCreated::class, LicenseDeactivated::class]);

        $user    = $this->createUser();
        $license = License::for($user)->product('pro')->create();
        $key     = $license->key;

        License::activate($key, 'example.com');
        License::deactivate($key, 'example.com');

        Event::assertDispatched(LicenseDeactivated::class, function ($event) {
            return $event->binding === 'example.com';
        });
    }

    public function test_license_revoked_event_is_dispatched(): void
    {
        Event::fake([LicenseCreated::class, LicenseRevoked::class]);

        $user    = $this->createUser();
        $license = License::for($user)->product('pro')->create();
        $key     = $license->key;

        License::revoke($key);

        Event::assertDispatched(LicenseRevoked::class);
    }

    public function test_license_created_event_carries_license_model(): void
    {
        Event::fake([LicenseCreated::class]);

        $user    = $this->createUser();
        $license = License::for($user)->product('enterprise')->seats(5)->create();

        Event::assertDispatched(LicenseCreated::class, function ($event) {
            return $event->license->seats === 5
                && $event->license->product === 'enterprise';
        });
    }

    public function test_license_activated_event_carries_activation_and_license(): void
    {
        Event::fake([LicenseCreated::class, LicenseActivated::class]);

        $user    = $this->createUser();
        $license = License::for($user)->product('basic')->seats(3)->create();

        License::activate($license->key, 'my-app.io');

        Event::assertDispatched(LicenseActivated::class, function ($event) use ($license) {
            return $event->license->product === 'basic'
                && $event->activation->getBinding() === 'my-app.io';
        });
    }
}
