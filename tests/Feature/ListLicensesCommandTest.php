<?php

namespace DevRavik\LaravelLicensing\Tests\Feature;

use DevRavik\LaravelLicensing\Facades\License;
use DevRavik\LaravelLicensing\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class ListLicensesCommandTest extends TestCase
{
    public function test_lists_all_licenses(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();

        License::for($user1)->product('pro')->create();
        License::for($user2)->product('basic')->create();

        $result = Artisan::call('licensing:list');

        $this->assertEquals(0, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('Licenses', $output);
        $this->assertStringContainsString('pro', $output);
        $this->assertStringContainsString('basic', $output);
    }

    public function test_filters_by_product(): void
    {
        $user = $this->createUser();

        License::for($user)->product('pro')->create();
        License::for($user)->product('basic')->create();

        $result = Artisan::call('licensing:list', ['--product' => 'pro']);

        $this->assertEquals(0, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('pro', $output);
        $this->assertStringNotContainsString('basic', $output);
    }

    public function test_filters_by_status(): void
    {
        $user = $this->createUser();

        $license1 = License::for($user)->product('pro')->create();
        $license2 = License::for($user)->product('basic')->create();

        License::revoke($license2->key);

        $result = Artisan::call('licensing:list', ['--status' => 'revoked']);

        $this->assertEquals(0, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('Revoked', $output);
    }

    public function test_shows_no_licenses_message_when_empty(): void
    {
        $result = Artisan::call('licensing:list');

        $this->assertEquals(0, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('No licenses found', $output);
    }
}
