<?php

namespace DevRavik\LaravelLicensing\Services\Generators;

use DevRavik\LaravelLicensing\Contracts\LicenseGeneratorInterface;

/**
 * Generates Ed25519-signed license keys using libsodium.
 *
 * The license key format is: base64(json_encode($payload) . '.' . $signature)
 * where the signature is an Ed25519 detached signature of the JSON payload.
 *
 * Supports both file paths and direct base64-encoded key strings for the private key.
 */
class SignedLicenseGenerator implements LicenseGeneratorInterface
{
    /**
     * Generate a signed license key from the provided payload.
     *
     * @param  array<string, mixed>  $payload  License metadata (product, seats, expires_at, owner info, etc.)
     * @return string Base64-encoded signed license key
     *
     * @throws \RuntimeException If private key is not configured or libsodium is unavailable
     */
    public function generate(array $payload): string
    {
        if (! function_exists('sodium_crypto_sign_detached')) {
            throw new \RuntimeException('libsodium extension not available');
        }

        // Get private key from config
        $privateKeyConfig = config('license.signature.private_key');
        if (empty($privateKeyConfig)) {
            throw new \RuntimeException('Private key not configured for signed license generation');
        }

        // Resolve the private key (file path or direct string)
        $privateKey = $this->resolveKey($privateKeyConfig);

        // Encode the payload as JSON
        $message = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($message === false) {
            throw new \RuntimeException('Failed to encode payload as JSON: '.json_last_error_msg());
        }

        // Create Ed25519 detached signature
        $signature = sodium_crypto_sign_detached($message, $privateKey);

        // Combine message and signature: message . '.' . signature
        $signed = $message.'.'.$signature;

        // Base64 encode the result
        return base64_encode($signed);
    }

    /**
     * Resolve a key from either a file path or a direct string.
     *
     * @param  string  $keyOrPath  File path or base64-encoded key string
     * @return string Binary key data
     *
     * @throws \RuntimeException If file path is provided but file doesn't exist
     */
    protected function resolveKey(string $keyOrPath): string
    {
        // Check if it's a file path - must start with / and file must exist
        // We check file_exists first to avoid treating valid base64 strings with / as paths
        if (str_starts_with($keyOrPath, '/') && file_exists($keyOrPath)) {
            $key = trim(file_get_contents($keyOrPath));
        } elseif (str_starts_with($keyOrPath, '/')) {
            // Starts with / but file doesn't exist
            // Check if it's a valid base64 string (base64 can start with /)
            $decoded = base64_decode($keyOrPath, true);
            if ($decoded !== false && strlen($decoded) >= 16) {
                // Valid base64 that decodes to reasonable length - treat as key string
                return $decoded;
            }
            // Not valid base64 or too short - treat as file path and throw error
            throw new \RuntimeException("Key file not found: {$keyOrPath}");
        } else {
            // Not a file path, treat as direct string
            $key = $keyOrPath;
        }

        // Decode base64 if needed (keys should be base64-encoded)
        $decoded = base64_decode($key, true);
        if ($decoded === false) {
            // If base64 decode fails, assume it's already binary or try using as-is
            // This handles edge cases where keys might be provided in different formats
            return $key;
        }

        return $decoded;
    }
}
