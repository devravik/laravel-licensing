<?php

namespace DevRavik\LaravelLicensing\Tests\Unit;

use DevRavik\LaravelLicensing\Exceptions\InvalidLicenseException;
use DevRavik\LaravelLicensing\Exceptions\LicenseAlreadyActivatedException;
use DevRavik\LaravelLicensing\Exceptions\LicenseExpiredException;
use DevRavik\LaravelLicensing\Exceptions\LicenseManagerException;
use DevRavik\LaravelLicensing\Exceptions\LicenseRevokedException;
use DevRavik\LaravelLicensing\Exceptions\SeatLimitExceededException;
use DevRavik\LaravelLicensing\Tests\TestCase;

class ExceptionTest extends TestCase
{
    public function test_all_exceptions_extend_base_exception(): void
    {
        $exceptions = [
            InvalidLicenseException::class,
            LicenseExpiredException::class,
            LicenseRevokedException::class,
            SeatLimitExceededException::class,
            LicenseAlreadyActivatedException::class,
        ];

        foreach ($exceptions as $class) {
            $this->assertTrue(
                is_subclass_of($class, LicenseManagerException::class),
                "{$class} does not extend LicenseManagerException"
            );
        }
    }

    public function test_invalid_license_exception_has_correct_status_code(): void
    {
        $e = InvalidLicenseException::forKey('fake-key');
        $this->assertSame(404, $e->getStatusCode());
    }

    public function test_exception_message_does_not_contain_raw_key(): void
    {
        $rawKey = 'super-secret-key-12345';
        $e      = InvalidLicenseException::forKey($rawKey);
        $this->assertStringNotContainsString($rawKey, $e->getMessage());
    }

    public function test_base_exception_has_500_status_code_by_default(): void
    {
        $e = LicenseManagerException::make('Something went wrong');
        $this->assertSame(500, $e->getStatusCode());
    }

    public function test_base_exception_make_accepts_custom_status_code(): void
    {
        $e = LicenseManagerException::make('Forbidden', 403);
        $this->assertSame(403, $e->getStatusCode());
        $this->assertSame('Forbidden', $e->getMessage());
    }

    public function test_all_exceptions_are_runtime_exceptions(): void
    {
        $exceptions = [
            InvalidLicenseException::class,
            LicenseExpiredException::class,
            LicenseRevokedException::class,
            SeatLimitExceededException::class,
            LicenseAlreadyActivatedException::class,
            LicenseManagerException::class,
        ];

        foreach ($exceptions as $class) {
            $this->assertTrue(
                is_subclass_of($class, \RuntimeException::class) || $class === LicenseManagerException::class,
                "{$class} does not extend RuntimeException"
            );
        }
    }
}
