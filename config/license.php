<?php

return [

    'license_model' => \DevRavik\LaravelLicensing\Models\License::class,

    'activation_model' => \DevRavik\LaravelLicensing\Models\Activation::class,

    'key_length' => env('LICENSE_KEY_LENGTH', 32),

    'hash_keys' => env('LICENSE_HASH_KEYS', true),

    'default_expiry_days' => env('LICENSE_DEFAULT_EXPIRY_DAYS', 365),

    'grace_period_days' => env('LICENSE_GRACE_PERIOD_DAYS', 7),

];
