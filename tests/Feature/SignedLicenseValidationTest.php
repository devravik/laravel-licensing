<?php

namespace DevRavik\LaravelLicensing\Tests\Feature;

use DevRavik\LaravelLicensing\Exceptions\InvalidSignatureException;
use DevRavik\LaravelLicensing\Facades\License;
use DevRavik\LaravelLicensing\Tests\TestCase;

class SignedLicenseValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('sodium_crypto_sign_detached')) {
            $this->markTestSkipped('libsodium extension not available');
        }
    }

    public function test_validates_signed_license_successfully(): void
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

        $validated = License::validate($rawKey);

        $this->assertTrue($validated->isValid());
        $this->assertFalse($validated->isExpired());
        $this->assertFalse($validated->isRevoked());
    }

    public function test_rejects_tampered_signed_license(): void
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

        // Tamper with the key (modify a character)
        $tamperedKey = substr($rawKey, 0, -1).'X';

        $this->expectException(InvalidSignatureException::class);

        License::validate($tamperedKey);
    }

    public function test_rejects_invalid_signature_format(): void
    {
        // Generate a test key pair
        $keypair = sodium_crypto_sign_keypair();
        $publicKey = base64_encode(sodium_crypto_sign_publickey($keypair));

        config()->set('license.license_generation', 'signed');
        config()->set('license.signature.public_key', $publicKey);

        // Create an invalid format (not base64, or missing separator)
        $invalidKey = 'not-a-valid-signed-key';

        $this->expectException(InvalidSignatureException::class);

        License::validate($invalidKey);
    }

    public function test_signed_license_validation_checks_expiry(): void
    {
        // Generate a test key pair
        $keypair = sodium_crypto_sign_keypair();
        $privateKey = base64_encode(sodium_crypto_sign_secretkey($keypair));
        $publicKey = base64_encode(sodium_crypto_sign_publickey($keypair));

        config()->set('license.license_generation', 'signed');
        config()->set('license.signature.private_key', $privateKey);
        config()->set('license.signature.public_key', $publicKey);
        config()->set('license.grace_period_days', 0);

        $user = $this->createUser();
        $license = License::for($user)->product('pro')->expiresInDays(1)->create();
        $rawKey = $license->key;

        // Manually push expires_at into the past
        $license->fresh()->update(['expires_at' => now()->subDays(1)]);

        $this->expectException(\DevRavik\LaravelLicensing\Exceptions\LicenseExpiredException::class);

        License::validate($rawKey);
    }

    public function test_signed_license_validation_checks_revocation(): void
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

        License::revoke($rawKey);

        $this->expectException(\DevRavik\LaravelLicensing\Exceptions\LicenseRevokedException::class);

        License::validate($rawKey);
    }

    public function test_signed_license_works_with_activation(): void
    {
        // Generate a test key pair
        $keypair = sodium_crypto_sign_keypair();
        $privateKey = base64_encode(sodium_crypto_sign_secretkey($keypair));
        $publicKey = base64_encode(sodium_crypto_sign_publickey($keypair));

        config()->set('license.license_generation', 'signed');
        config()->set('license.signature.private_key', $privateKey);
        config()->set('license.signature.public_key', $publicKey);

        $user = $this->createUser();
        $license = License::for($user)->product('pro')->seats(2)->create();
        $rawKey = $license->key;

        // Should be able to activate
        $activation = License::activate($rawKey, 'domain1.com');

        $this->assertNotNull($activation);
        $this->assertSame('domain1.com', $activation->binding);

        // Should be able to activate second seat
        $activation2 = License::activate($rawKey, 'domain2.com');
        $this->assertNotNull($activation2);
    }
}
