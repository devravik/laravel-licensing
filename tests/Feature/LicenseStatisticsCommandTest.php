<?php

namespace DevRavik\LaravelLicensing\Tests\Feature;

use Carbon\Carbon;
use DevRavik\LaravelLicensing\Facades\License;
use DevRavik\LaravelLicensing\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class LicenseStatisticsCommandTest extends TestCase
{
    public function test_displays_statistics(): void
    {
        $user = $this->createUser();

        License::for($user)->product('pro')->seats(5)->create();
        License::for($user)->product('basic')->create();

        $result = Artisan::call('licensing:stats');

        $this->assertEquals(0, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('License Statistics', $output);
        $this->assertStringContainsString('Total:', $output);
        $this->assertStringContainsString('Active:', $output);
    }

    public function test_filters_by_product(): void
    {
        $user = $this->createUser();

        License::for($user)->product('pro')->create();
        License::for($user)->product('basic')->create();

        $result = Artisan::call('licensing:stats', ['--product' => 'pro']);

        $this->assertEquals(0, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('Product: pro', $output);
    }

    public function test_shows_expired_and_revoked_counts(): void
    {
        $user = $this->createUser();

        $license1 = License::for($user)->product('pro')->expiresInDays(1)->create();
        $license2 = License::for($user)->product('basic')->create();

        $license1->update(['expires_at' => Carbon::yesterday()]);
        License::revoke($license2->key);

        $result = Artisan::call('licensing:stats');

        $this->assertEquals(0, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('Expired:', $output);
        $this->assertStringContainsString('Revoked:', $output);
    }
}
