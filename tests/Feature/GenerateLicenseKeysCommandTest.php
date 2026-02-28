<?php

namespace DevRavik\LaravelLicensing\Tests\Feature;

use DevRavik\LaravelLicensing\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class GenerateLicenseKeysCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('sodium_crypto_sign_keypair')) {
            $this->markTestSkipped('libsodium extension not available');
        }
    }

    public function test_generates_key_pair_successfully(): void
    {
        // Clear any existing keys
        config()->set('license.signature.private_key', null);
        config()->set('license.signature.public_key', null);

        $result = Artisan::call('licensing:keys');

        $this->assertEquals(0, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('Ed25519 key pair generated successfully', $output);
        $this->assertStringContainsString('LICENSE_PRIVATE_KEY=', $output);
        $this->assertStringContainsString('LICENSE_PUBLIC_KEY=', $output);
    }

    public function test_shows_keys_when_show_option_used(): void
    {
        config()->set('license.signature.private_key', null);
        config()->set('license.signature.public_key', null);

        $result = Artisan::call('licensing:keys', ['--show' => true]);

        $this->assertEquals(0, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('Private Key:', $output);
        $this->assertStringContainsString('Public Key:', $output);
    }

    public function test_fails_when_keys_exist_without_force(): void
    {
        config()->set('license.signature.private_key', 'existing_key');
        config()->set('license.signature.public_key', 'existing_key');

        $result = Artisan::call('licensing:keys');

        $this->assertEquals(1, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('Keys already exist', $output);
        $this->assertStringContainsString('Use --force to overwrite', $output);
    }

    public function test_overwrites_existing_keys_with_force(): void
    {
        config()->set('license.signature.private_key', 'old_key');
        config()->set('license.signature.public_key', 'old_key');

        $result = Artisan::call('licensing:keys', ['--force' => true]);

        $this->assertEquals(0, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('Ed25519 key pair generated successfully', $output);
    }

    public function test_displays_security_warning(): void
    {
        config()->set('license.signature.private_key', null);
        config()->set('license.signature.public_key', null);

        $result = Artisan::call('licensing:keys');

        $this->assertEquals(0, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('SECURITY WARNING', $output);
        $this->assertStringContainsString('Private key should NEVER', $output);
    }

    public function test_fails_when_libsodium_not_available(): void
    {
        // This test would need to run in an environment without libsodium
        // For now, we skip it if libsodium is available
        if (function_exists('sodium_crypto_sign_keypair')) {
            $this->markTestSkipped('libsodium is available');
        }

        $result = Artisan::call('licensing:keys');

        $this->assertEquals(1, $result);
        $output = Artisan::output();
        $this->assertStringContainsString('libsodium extension is not available', $output);
    }
}
