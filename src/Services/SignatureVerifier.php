<?php

namespace DevRavik\LaravelLicensing\Services;

use DevRavik\LaravelLicensing\Exceptions\InvalidSignatureException;

/**
 * Verifies Ed25519 signatures for signed license keys.
 *
 * Supports both file paths and direct base64-encoded key strings.
 */
class SignatureVerifier
{
    /**
     * Verify a signed license key's signature.
     *
     * @param  string  $signedKey  Base64-encoded string in format: base64(message . '.' . signature)
     * @return array{message: string, payload: array<string, mixed>} Decoded message and parsed payload
     *
     * @throws InvalidSignatureException If signature verification fails
     */
    public function verify(string $signedKey): array
    {
        // Check public key configuration first
        $publicKeyConfig = config('license.signature.public_key');
        if (empty($publicKeyConfig)) {
            throw InvalidSignatureException::verificationFailed('Public key not configured');
        }

        // Resolve the public key early to validate it exists (file path or direct string)
        // This allows file-not-found errors to bubble up before decoding
        try {
            $publicKey = $this->resolveKey($publicKeyConfig);
        } catch (\RuntimeException $e) {
            // Re-throw RuntimeException (e.g., file not found) as-is
            throw $e;
        }

        // Decode the base64-encoded signed key
        $decoded = base64_decode($signedKey, true);
        if ($decoded === false) {
            throw InvalidSignatureException::verificationFailed('Invalid base64 encoding');
        }

        // Split message and signature (format: message . '.' . signature)
        $parts = explode('.', $decoded, 2);
        if (count($parts) !== 2) {
            throw InvalidSignatureException::verificationFailed('Invalid signed key format');
        }

        [$message, $signature] = $parts;

        // Verify the signature using Ed25519
        if (! function_exists('sodium_crypto_sign_verify_detached')) {
            throw InvalidSignatureException::verificationFailed('libsodium extension not available');
        }

        $isValid = sodium_crypto_sign_verify_detached(
            $signature,
            $message,
            $publicKey
        );

        if (! $isValid) {
            throw InvalidSignatureException::verificationFailed('Signature verification failed');
        }

        // Parse the JSON payload
        $payload = json_decode($message, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw InvalidSignatureException::verificationFailed('Invalid JSON payload in signed key');
        }

        return [
            'message' => $message,
            'payload' => $payload,
        ];
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
