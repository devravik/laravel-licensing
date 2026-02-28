<?php

namespace DevRavik\LaravelLicensing\Tests\Feature;

use DevRavik\LaravelLicensing\Facades\License;
use DevRavik\LaravelLicensing\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class DeactivateLicenseCommandTest extends TestCase
{
    public function test_deactivates_license_binding(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->create();
        $rawKey = $license->key;

        License::activate($rawKey, 'domain.com');

        $result = Artisan::call('licensing:deactivate', [
            '--key' => $rawKey,
            '--binding' => 'domain.com',
        ]);

        $this->assertEquals(0, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('deactivated successfully', $output);

        $this->assertDatabaseMissing('license_activations', [
            'license_id' => $license->id,
            'binding' => 'domain.com',
        ]);
    }

    public function test_fails_when_license_not_found(): void
    {
        $result = Artisan::call('licensing:deactivate', [
            '--key' => 'invalid-key',
            '--binding' => 'domain.com',
        ]);

        $this->assertEquals(1, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('License not found', $output);
    }
}
