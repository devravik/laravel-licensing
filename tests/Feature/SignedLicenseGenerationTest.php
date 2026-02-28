<?php

namespace DevRavik\LaravelLicensing\Tests\Feature;

use DevRavik\LaravelLicensing\Facades\License;
use DevRavik\LaravelLicensing\Tests\TestCase;

class SignedLicenseGenerationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('sodium_crypto_sign_detached')) {
            $this->markTestSkipped('libsodium extension not available');
        }
    }

    public function test_can_create_signed_license(): void
    {
        // Generate a test key pair
        $keypair = sodium_crypto_sign_keypair();
        $privateKey = base64_encode(sodium_crypto_sign_secretkey($keypair));
        $publicKey = base64_encode(sodium_crypto_sign_publickey($keypair));

        config()->set('license.license_generation', 'signed');
        config()->set('license.signature.private_key', $privateKey);
        config()->set('license.signature.public_key', $publicKey);

        $user = $this->createUser();

        $license = License::for($user)
            ->product('pro')
            ->seats(3)
            ->expiresInDays(365)
            ->create();

        $this->assertNotNull($license->key);
        $this->assertNotEmpty($license->key);
        $this->assertSame('pro', $license->product);
        $this->assertSame(3, $license->seats);

        // The key should be base64-encoded and contain a signature
        $decoded = base64_decode($license->key, true);
        $this->assertNotFalse($decoded);
        $this->assertStringContainsString('.', $decoded);
    }

    public function test_signed_license_can_be_validated(): void
    {
        // Generate a test key pair
        $keypair = sodium_crypto_sign_keypair();
        $privateKey = base64_encode(sodium_crypto_sign_secretkey($keypair));
        $publicKey = base64_encode(sodium_crypto_sign_publickey($keypair));

        config()->set('license.license_generation', 'signed');
        config()->set('license.signature.private_key', $privateKey);
        config()->set('license.signature.public_key', $publicKey);

        $user = $this->createUser();
        $license = License::for($user)->product('pro')->create();
        $rawKey = $license->key;

        // Should be able to validate the signed license
        $validated = License::validate($rawKey);

        $this->assertNotNull($validated);
        $this->assertSame('pro', $validated->product);
    }

    public function test_signed_license_works_with_file_path_keys(): void
    {
        // Generate a test key pair
        $keypair = sodium_crypto_sign_keypair();
        $privateKey = base64_encode(sodium_crypto_sign_secretkey($keypair));
        $publicKey = base64_encode(sodium_crypto_sign_publickey($keypair));

        // Create temporary files
        $privateKeyFile = sys_get_temp_dir().'/test_private_'.uniqid().'.key';
        $publicKeyFile = sys_get_temp_dir().'/test_public_'.uniqid().'.key';

        file_put_contents($privateKeyFile, $privateKey);
        file_put_contents($publicKeyFile, $publicKey);

        try {
            config()->set('license.license_generation', 'signed');
            config()->set('license.signature.private_key', $privateKeyFile);
            config()->set('license.signature.public_key', $publicKeyFile);

            $user = $this->createUser();
            $license = License::for($user)->product('pro')->create();

            $this->assertNotNull($license->key);
            $this->assertNotEmpty($license->key);

            // Should be able to validate
            $validated = License::validate($license->key);
            $this->assertNotNull($validated);
        } finally {
            if (file_exists($privateKeyFile)) {
                unlink($privateKeyFile);
            }
            if (file_exists($publicKeyFile)) {
                unlink($publicKeyFile);
            }
        }
    }

    public function test_signed_license_still_stored_in_database(): void
    {
        // Generate a test key pair
        $keypair = sodium_crypto_sign_keypair();
        $privateKey = base64_encode(sodium_crypto_sign_secretkey($keypair));
        $publicKey = base64_encode(sodium_crypto_sign_publickey($keypair));

        config()->set('license.license_generation', 'signed');
        config()->set('license.signature.private_key', $privateKey);
        config()->set('license.signature.public_key', $publicKey);

        $user = $this->createUser();
        $license = License::for($user)->product('pro')->create();

        // Should be stored in database
        $this->assertDatabaseHas('licenses', [
            'product' => 'pro',
            'owner_id' => $user->id,
            'owner_type' => get_class($user),
        ]);

        // Raw key should not be in database (it's hashed)
        $this->assertDatabaseMissing('licenses', ['key' => $license->key]);
    }
}
