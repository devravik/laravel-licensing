<?php

namespace DevRavik\LaravelLicensing\Tests\Unit;

use DevRavik\LaravelLicensing\KeyGenerator;
use PHPUnit\Framework\TestCase;

class KeyGeneratorTest extends TestCase
{
    private KeyGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new KeyGenerator();
    }

    // -------------------------------------------------------------------------
    // Output length
    // -------------------------------------------------------------------------

    public function test_returns_a_string_of_the_exact_requested_length(): void
    {
        foreach ([16, 24, 32, 64, 128] as $length) {
            $key = $this->generator->generate($length);
            $this->assertSame(
                $length,
                strlen($key),
                "Expected key length {$length}, got " . strlen($key)
            );
        }
    }

    public function test_returns_only_lowercase_hex_characters(): void
    {
        $key = $this->generator->generate(32);
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $key);
    }

    // -------------------------------------------------------------------------
    // Entropy / uniqueness
    // -------------------------------------------------------------------------

    public function test_two_consecutive_keys_are_not_equal(): void
    {
        $a = $this->generator->generate(32);
        $b = $this->generator->generate(32);
        $this->assertNotSame($a, $b);
    }

    public function test_generates_unique_keys_in_a_large_batch(): void
    {
        $keys = [];
        for ($i = 0; $i < 100; $i++) {
            $keys[] = $this->generator->generate(32);
        }
        // array_unique collapses duplicates; counts should still be equal.
        $this->assertCount(100, array_unique($keys));
    }

    // -------------------------------------------------------------------------
    // Boundary: minimum length (16)
    // -------------------------------------------------------------------------

    public function test_accepts_the_minimum_length_of_16(): void
    {
        $key = $this->generator->generate(16);
        $this->assertSame(16, strlen($key));
    }

    public function test_rejects_a_length_below_minimum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/between 16 and 128/');
        $this->generator->generate(15);
    }

    // -------------------------------------------------------------------------
    // Boundary: maximum length (128)
    // -------------------------------------------------------------------------

    public function test_accepts_the_maximum_length_of_128(): void
    {
        $key = $this->generator->generate(128);
        $this->assertSame(128, strlen($key));
    }

    public function test_rejects_a_length_above_maximum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/between 16 and 128/');
        $this->generator->generate(129);
    }

    // -------------------------------------------------------------------------
    // Odd lengths (ceil behaviour)
    // -------------------------------------------------------------------------

    public function test_handles_odd_key_lengths_correctly(): void
    {
        foreach ([17, 33, 63, 127] as $length) {
            $key = $this->generator->generate($length);
            $this->assertSame($length, strlen($key));
            $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $key);
        }
    }
}
