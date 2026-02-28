<?php

namespace DevRavik\LaravelLicensing\Tests\Feature;

use DevRavik\LaravelLicensing\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class CreateLicenseCommandTest extends TestCase
{
    public function test_creates_license_non_interactively(): void
    {
        $user = $this->createUser();

        $result = Artisan::call('licensing:create', [
            '--owner-type' => get_class($user),
            '--owner-id' => $user->id,
            '--product' => 'pro',
            '--seats' => 5,
            '--expires-days' => 30,
            '--non-interactive' => true,
        ]);

        $this->assertEquals(0, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('License created successfully', $output);
        $this->assertStringContainsString('pro', $output);
        $this->assertStringContainsString('5', $output);
        $this->assertStringContainsString('License Key:', $output);

        $this->assertDatabaseHas('licenses', [
            'product' => 'pro',
            'owner_id' => $user->id,
            'owner_type' => get_class($user),
            'seats' => 5,
        ]);
    }

    public function test_fails_with_invalid_owner(): void
    {
        $result = Artisan::call('licensing:create', [
            '--owner-type' => 'App\\Models\\NonExistent',
            '--owner-id' => 999,
            '--product' => 'pro',
            '--non-interactive' => true,
        ]);

        $this->assertEquals(1, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('not found', $output);
    }

    public function test_fails_without_product(): void
    {
        $user = $this->createUser();

        $result = Artisan::call('licensing:create', [
            '--owner-type' => get_class($user),
            '--owner-id' => $user->id,
            '--non-interactive' => true,
        ]);

        $this->assertEquals(1, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('Product is required', $output);
    }
}
