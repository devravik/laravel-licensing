<?php

namespace DevRavik\LaravelLicensing\Tests\Unit;

use DevRavik\LaravelLicensing\Services\Generators\RandomLicenseGenerator;
use DevRavik\LaravelLicensing\Tests\TestCase;

class RandomLicenseGeneratorTest extends TestCase
{
    private RandomLicenseGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new RandomLicenseGenerator;
    }

    // -------------------------------------------------------------------------
    // Output length
    // -------------------------------------------------------------------------

    public function test_returns_a_string_of_the_exact_configured_length(): void
    {
        config()->set('license.key_length', 32);

        $key = $this->generator->generate([]);
        $this->assertSame(32, strlen($key));
    }

    public function test_returns_only_lowercase_hex_characters(): void
    {
        config()->set('license.key_length', 32);

        $key = $this->generator->generate([]);
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $key);
    }

    public function test_ignores_payload_for_random_generation(): void
    {
        config()->set('license.key_length', 32);

        $key1 = $this->generator->generate(['product' => 'pro', 'seats' => 5]);
        $key2 = $this->generator->generate(['product' => 'basic', 'seats' => 1]);

        // Both should be random and different
        $this->assertNotSame($key1, $key2);
        $this->assertSame(32, strlen($key1));
        $this->assertSame(32, strlen($key2));
    }

    // -------------------------------------------------------------------------
    // Entropy / uniqueness
    // -------------------------------------------------------------------------

    public function test_two_consecutive_keys_are_not_equal(): void
    {
        config()->set('license.key_length', 32);

        $a = $this->generator->generate([]);
        $b = $this->generator->generate([]);
        $this->assertNotSame($a, $b);
    }

    public function test_generates_unique_keys_in_a_large_batch(): void
    {
        config()->set('license.key_length', 32);

        $keys = [];
        for ($i = 0; $i < 100; $i++) {
            $keys[] = $this->generator->generate([]);
        }
        // array_unique collapses duplicates; counts should still be equal.
        $this->assertCount(100, array_unique($keys));
    }

    // -------------------------------------------------------------------------
    // Boundary: minimum length (16)
    // -------------------------------------------------------------------------

    public function test_accepts_the_minimum_length_of_16(): void
    {
        config()->set('license.key_length', 16);

        $key = $this->generator->generate([]);
        $this->assertSame(16, strlen($key));
    }

    public function test_rejects_a_length_below_minimum(): void
    {
        config()->set('license.key_length', 15);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/between 16 and 128/');
        $this->generator->generate([]);
    }

    // -------------------------------------------------------------------------
    // Boundary: maximum length (128)
    // -------------------------------------------------------------------------

    public function test_accepts_the_maximum_length_of_128(): void
    {
        config()->set('license.key_length', 128);

        $key = $this->generator->generate([]);
        $this->assertSame(128, strlen($key));
    }

    public function test_rejects_a_length_above_maximum(): void
    {
        config()->set('license.key_length', 129);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/between 16 and 128/');
        $this->generator->generate([]);
    }

    // -------------------------------------------------------------------------
    // Odd lengths (ceil behaviour)
    // -------------------------------------------------------------------------

    public function test_handles_odd_key_lengths_correctly(): void
    {
        foreach ([17, 33, 63, 127] as $length) {
            config()->set('license.key_length', $length);

            $key = $this->generator->generate([]);
            $this->assertSame($length, strlen($key));
            $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $key);
        }
    }
}
