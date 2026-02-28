<?php

return [

    /*
    |--------------------------------------------------------------------------
    | License Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model used to represent a license. You may replace this
    | with your own model as long as it extends the base License model or
    | implements the LicenseContract interface.
    |
    */
    'license_model' => \DevRavik\LaravelLicensing\Models\License::class,

    /*
    |--------------------------------------------------------------------------
    | Activation Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model used to represent a license activation (seat binding).
    | You may replace this with your own model as long as it extends the base
    | Activation model or implements the ActivationContract interface.
    |
    */
    'activation_model' => \DevRavik\LaravelLicensing\Models\Activation::class,

    /*
    |--------------------------------------------------------------------------
    | Key Length
    |--------------------------------------------------------------------------
    |
    | The length of generated license keys in characters. Longer keys provide
    | more entropy and are harder to guess. The default of 32 characters
    | provides 128 bits of entropy when using hexadecimal encoding.
    |
    | Minimum recommended: 16 | Default: 32 | Maximum: 128
    |
    */
    'key_length' => env('LICENSE_KEY_LENGTH', 32),

    /*
    |--------------------------------------------------------------------------
    | Hash Keys
    |--------------------------------------------------------------------------
    |
    | When enabled, license keys are hashed (using bcrypt or the configured
    | hashing driver) before being stored in the database. This ensures that
    | plaintext keys are never persisted. The raw key is returned only once
    | during creation.
    |
    | WARNING: Disabling this option stores keys in plaintext and is strongly
    | discouraged in production environments.
    |
    */
    'hash_keys' => env('LICENSE_HASH_KEYS', true),

    /*
    |--------------------------------------------------------------------------
    | Default Expiry (Days)
    |--------------------------------------------------------------------------
    |
    | The default number of days before a newly created license expires. This
    | value is used when no explicit expiry is set during license creation.
    | Set to null for licenses that never expire by default.
    |
    */
    'default_expiry_days' => env('LICENSE_DEFAULT_EXPIRY_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Grace Period (Days)
    |--------------------------------------------------------------------------
    |
    | The number of days after a license expires during which it remains
    | temporarily valid. This is useful for subscription-based systems where
    | you want to give users time to renew before cutting off access.
    |
    | Set to 0 to disable grace periods entirely.
    |
    */
    'grace_period_days' => env('LICENSE_GRACE_PERIOD_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | License Generation Strategy
    |--------------------------------------------------------------------------
    |
    | The strategy used to generate license keys. Options:
    |
    | - 'random': Generates cryptographically secure random keys (default)
    | - 'signed': Generates Ed25519-signed license keys with embedded payload
    |
    | When using 'signed', you must configure the signature keys below.
    |
    */
    'license_generation' => env('LICENSE_GENERATION', 'random'),

    /*
    |--------------------------------------------------------------------------
    | Signature Configuration
    |--------------------------------------------------------------------------
    |
    | Ed25519 public and private keys for signed license generation.
    | Required when 'license_generation' is set to 'signed'.
    |
    | Keys can be provided as:
    | - File paths: '/path/to/key.file' (file contains base64-encoded key)
    | - Direct strings: 'base64_encoded_key_string' (base64-encoded key)
    |
    | Generate a key pair using:
    |   $keypair = sodium_crypto_sign_keypair();
    |   $publicKey = base64_encode(sodium_crypto_sign_publickey($keypair));
    |   $privateKey = base64_encode(sodium_crypto_sign_secretkey($keypair));
    |
    */
    'signature' => [
        'public_key' => env('LICENSE_PUBLIC_KEY'),
        'private_key' => env('LICENSE_PRIVATE_KEY'),
    ],

];
