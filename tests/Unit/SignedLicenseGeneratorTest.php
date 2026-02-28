<?php

namespace DevRavik\LaravelLicensing\Tests\Unit;

use DevRavik\LaravelLicensing\Services\Generators\SignedLicenseGenerator;
use DevRavik\LaravelLicensing\Tests\TestCase;

class SignedLicenseGeneratorTest extends TestCase
{
    private SignedLicenseGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('sodium_crypto_sign_detached')) {
            $this->markTestSkipped('libsodium extension not available');
        }

        $this->generator = new SignedLicenseGenerator;
    }

    public function test_generates_signed_license_key(): void
    {
        // Generate a test key pair
        $keypair = sodium_crypto_sign_keypair();
        $privateKey = base64_encode(sodium_crypto_sign_secretkey($keypair));

        config()->set('license.signature.private_key', $privateKey);

        $payload = [
            'product' => 'pro',
            'seats' => 5,
            'expires_at' => now()->addDays(365)->toIso8601String(),
            'owner_type' => 'App\\Models\\User',
            'owner_id' => 1,
        ];

        $signedKey = $this->generator->generate($payload);

        // Should be base64-encoded
        $this->assertNotEmpty($signedKey);
        $decoded = base64_decode($signedKey, true);
        $this->assertNotFalse($decoded);

        // Should contain message and signature separated by '.'
        $parts = explode('.', $decoded, 2);
        $this->assertCount(2, $parts);
        [$message, $signature] = $parts;

        // Message should be valid JSON
        $decodedPayload = json_decode($message, true);
        $this->assertNotNull($decodedPayload);
        $this->assertSame('pro', $decodedPayload['product']);
        $this->assertSame(5, $decodedPayload['seats']);

        // Signature should be valid Ed25519 signature
        $publicKey = sodium_crypto_sign_publickey($keypair);
        $isValid = sodium_crypto_sign_verify_detached($signature, $message, $publicKey);
        $this->assertTrue($isValid);
    }

    public function test_throws_exception_when_private_key_not_configured(): void
    {
        config()->set('license.signature.private_key', null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Private key not configured');

        $this->generator->generate(['product' => 'pro']);
    }

    public function test_supports_file_path_for_private_key(): void
    {
        // Generate a test key pair
        $keypair = sodium_crypto_sign_keypair();
        $privateKey = base64_encode(sodium_crypto_sign_secretkey($keypair));

        // Create a temporary file with the key
        $tempFile = sys_get_temp_dir().'/test_private_key_'.uniqid().'.key';
        file_put_contents($tempFile, $privateKey);

        try {
            config()->set('license.signature.private_key', $tempFile);

            $payload = ['product' => 'pro', 'seats' => 1];
            $signedKey = $this->generator->generate($payload);

            $this->assertNotEmpty($signedKey);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function test_throws_exception_when_key_file_not_found(): void
    {
        config()->set('license.signature.private_key', '/nonexistent/path/to/key.key');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Key file not found');

        $this->generator->generate(['product' => 'pro']);
    }

    public function test_throws_exception_when_libsodium_not_available(): void
    {
        // This test would need to be run in an environment without libsodium
        // For now, we skip it if libsodium is available
        if (function_exists('sodium_crypto_sign_detached')) {
            $this->markTestSkipped('libsodium is available');
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('libsodium extension not available');

        $this->generator->generate(['product' => 'pro']);
    }
}
