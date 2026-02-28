<?php

namespace DevRavik\LaravelLicensing\Tests\Unit;

use DevRavik\LaravelLicensing\Exceptions\InvalidSignatureException;
use DevRavik\LaravelLicensing\Services\SignatureVerifier;
use DevRavik\LaravelLicensing\Tests\TestCase;

class SignatureVerifierTest extends TestCase
{
    private SignatureVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('sodium_crypto_sign_detached')) {
            $this->markTestSkipped('libsodium extension not available');
        }

        $this->verifier = new SignatureVerifier;
    }

    public function test_verifies_valid_signature(): void
    {
        // Generate a test key pair
        $keypair = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keypair);
        $publicKey = base64_encode(sodium_crypto_sign_publickey($keypair));

        config()->set('license.signature.public_key', $publicKey);

        // Create a signed message
        $message = json_encode(['product' => 'pro', 'seats' => 5]);
        $signature = sodium_crypto_sign_detached($message, $privateKey);
        $signedKey = base64_encode($message.'.'.$signature);

        $result = $this->verifier->verify($signedKey);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('payload', $result);
        $this->assertSame('pro', $result['payload']['product']);
        $this->assertSame(5, $result['payload']['seats']);
    }

    public function test_rejects_tampered_message(): void
    {
        // Generate a test key pair
        $keypair = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keypair);
        $publicKey = base64_encode(sodium_crypto_sign_publickey($keypair));

        config()->set('license.signature.public_key', $publicKey);

        // Create a signed message
        $message = json_encode(['product' => 'pro', 'seats' => 5]);
        $signature = sodium_crypto_sign_detached($message, $privateKey);

        // Tamper with the message
        $tamperedMessage = json_encode(['product' => 'enterprise', 'seats' => 10]);
        $signedKey = base64_encode($tamperedMessage.'.'.$signature);

        $this->expectException(InvalidSignatureException::class);
        $this->expectExceptionMessage('Signature verification failed');

        $this->verifier->verify($signedKey);
    }

    public function test_rejects_invalid_signature(): void
    {
        // Generate a test key pair
        $keypair = sodium_crypto_sign_keypair();
        $publicKey = base64_encode(sodium_crypto_sign_publickey($keypair));

        config()->set('license.signature.public_key', $publicKey);

        // Create a message with a fake signature
        $message = json_encode(['product' => 'pro', 'seats' => 5]);
        $fakeSignature = str_repeat('x', 64); // Invalid signature
        $signedKey = base64_encode($message.'.'.$fakeSignature);

        $this->expectException(InvalidSignatureException::class);
        $this->expectExceptionMessage('Signature verification failed');

        $this->verifier->verify($signedKey);
    }

    public function test_rejects_invalid_base64(): void
    {
        config()->set('license.signature.public_key', base64_encode(sodium_crypto_sign_publickey(sodium_crypto_sign_keypair())));

        $this->expectException(InvalidSignatureException::class);
        $this->expectExceptionMessage('Invalid base64 encoding');

        $this->verifier->verify('not-valid-base64!!!');
    }

    public function test_rejects_invalid_format(): void
    {
        config()->set('license.signature.public_key', base64_encode(sodium_crypto_sign_publickey(sodium_crypto_sign_keypair())));

        // Missing the '.' separator
        $invalid = base64_encode('just-a-message');
        $this->expectException(InvalidSignatureException::class);
        $this->expectExceptionMessage('Invalid signed key format');

        $this->verifier->verify($invalid);
    }

    public function test_throws_exception_when_public_key_not_configured(): void
    {
        config()->set('license.signature.public_key', null);

        $this->expectException(InvalidSignatureException::class);
        $this->expectExceptionMessage('Public key not configured');

        $this->verifier->verify('dummy');
    }

    public function test_supports_file_path_for_public_key(): void
    {
        // Generate a test key pair
        $keypair = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keypair);
        $publicKey = base64_encode(sodium_crypto_sign_publickey($keypair));

        // Create a temporary file with the public key
        $tempFile = sys_get_temp_dir().'/test_public_key_'.uniqid().'.key';
        file_put_contents($tempFile, $publicKey);

        try {
            config()->set('license.signature.public_key', $tempFile);

            // Create a signed message
            $message = json_encode(['product' => 'pro', 'seats' => 5]);
            $signature = sodium_crypto_sign_detached($message, $privateKey);
            $signedKey = base64_encode($message.'.'.$signature);

            $result = $this->verifier->verify($signedKey);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('payload', $result);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function test_throws_exception_when_key_file_not_found(): void
    {
        config()->set('license.signature.public_key', '/nonexistent/path/to/key.key');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Key file not found');

        $this->verifier->verify('dummy');
    }

    public function test_rejects_invalid_json_payload(): void
    {
        // Generate a test key pair
        $keypair = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keypair);
        $publicKey = base64_encode(sodium_crypto_sign_publickey($keypair));

        config()->set('license.signature.public_key', $publicKey);

        // Create a message with invalid JSON
        $invalidJson = 'not valid json {';
        $signature = sodium_crypto_sign_detached($invalidJson, $privateKey);
        $signedKey = base64_encode($invalidJson.'.'.$signature);

        // This should pass signature verification but fail on JSON parsing
        // Actually, let's create a valid signature for invalid JSON
        $this->expectException(InvalidSignatureException::class);
        $this->expectExceptionMessage('Invalid JSON payload');

        $this->verifier->verify($signedKey);
    }
}
