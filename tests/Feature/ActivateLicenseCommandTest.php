<?php

namespace DevRavik\LaravelLicensing\Tests\Feature;

use DevRavik\LaravelLicensing\Facades\License;
use DevRavik\LaravelLicensing\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class ActivateLicenseCommandTest extends TestCase
{
    public function test_activates_license_by_key(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->seats(3)->create();
        $rawKey = $license->key;

        $result = Artisan::call('licensing:activate', [
            '--key' => $rawKey,
            '--binding' => 'domain.com',
        ]);

        $this->assertEquals(0, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('activated successfully', $output);
        $this->assertStringContainsString('domain.com', $output);

        $this->assertDatabaseHas('license_activations', [
            'license_id' => $license->id,
            'binding' => 'domain.com',
        ]);
    }

    public function test_activates_license_by_id(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->create();
        $rawKey = $license->key;

        $result = Artisan::call('licensing:activate', [
            '--id' => $license->id,
            '--binding' => 'test.com',
        ]);

        $this->assertEquals(0, $result);
        $this->assertDatabaseHas('license_activations', [
            'license_id' => $license->id,
            'binding' => 'test.com',
        ]);
    }

    public function test_fails_when_license_not_found(): void
    {
        $result = Artisan::call('licensing:activate', [
            '--key' => 'invalid-key',
            '--binding' => 'domain.com',
        ]);

        $this->assertEquals(1, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('License not found', $output);
    }
}
