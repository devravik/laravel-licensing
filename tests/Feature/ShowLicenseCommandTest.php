<?php

namespace DevRavik\LaravelLicensing\Tests\Feature;

use DevRavik\LaravelLicensing\Facades\License;
use DevRavik\LaravelLicensing\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class ShowLicenseCommandTest extends TestCase
{
    public function test_shows_license_by_key(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->seats(5)->create();
        $rawKey = $license->key;

        $result = Artisan::call('licensing:show', ['--key' => $rawKey]);

        $this->assertEquals(0, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('License Details', $output);
        $this->assertStringContainsString('pro', $output);
        $this->assertStringContainsString('5', $output);
    }

    public function test_shows_license_by_id(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('basic')->create();

        $result = Artisan::call('licensing:show', ['--id' => $license->id]);

        $this->assertEquals(0, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('License Details', $output);
        $this->assertStringContainsString('basic', $output);
    }

    public function test_shows_full_key_with_full_option(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->create();
        $rawKey = $license->key;

        $result = Artisan::call('licensing:show', ['--key' => $rawKey, '--full' => true]);

        $this->assertEquals(0, $result);
        $output = Artisan::output();
        $this->assertStringContainsString($rawKey, $output);
    }

    public function test_fails_when_license_not_found(): void
    {
        $result = Artisan::call('licensing:show', ['--key' => 'invalid-key']);

        $this->assertEquals(1, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('License not found', $output);
    }

    public function test_fails_when_no_key_or_id_provided(): void
    {
        $result = Artisan::call('licensing:show');

        $this->assertEquals(1, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('Either --key or --id must be provided', $output);
    }
}
