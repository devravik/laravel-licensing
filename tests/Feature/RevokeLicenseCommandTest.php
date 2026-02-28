<?php

namespace DevRavik\LaravelLicensing\Tests\Feature;

use DevRavik\LaravelLicensing\Facades\License;
use DevRavik\LaravelLicensing\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class RevokeLicenseCommandTest extends TestCase
{
    public function test_revokes_license_by_key(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->create();
        $rawKey = $license->key;

        $result = Artisan::call('licensing:revoke', [
            '--key' => $rawKey,
            '--force' => true,
        ]);

        $this->assertEquals(0, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('revoked successfully', $output);

        $this->assertTrue($license->fresh()->isRevoked());
    }

    public function test_revokes_license_by_id(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->create();

        $result = Artisan::call('licensing:revoke', [
            '--id' => $license->id,
            '--force' => true,
        ]);

        $this->assertEquals(0, $result);
        $this->assertTrue($license->fresh()->isRevoked());
    }

    public function test_fails_when_license_not_found(): void
    {
        $result = Artisan::call('licensing:revoke', [
            '--key' => 'invalid-key',
            '--force' => true,
        ]);

        $this->assertEquals(1, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('License not found', $output);
    }

    public function test_warns_when_already_revoked(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->create();
        License::revoke($license->key);

        $result = Artisan::call('licensing:revoke', [
            '--key' => $license->key,
            '--force' => true,
        ]);

        $this->assertEquals(0, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('already revoked', $output);
    }
}
