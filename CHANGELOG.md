# Changelog

All notable changes to `devravik/laravel-licensing` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-02-28

### Added
- **Ed25519 Signature-Based License Verification**: Optional cryptographic signature-based license generation and verification using libsodium
  - Strategy pattern implementation with `LicenseGeneratorInterface`
  - `RandomLicenseGenerator` for default random key generation (backward compatible)
  - `SignedLicenseGenerator` for Ed25519 signed license keys
  - `SignatureVerifier` service for signature validation
  - Support for both file paths and direct base64-encoded key strings
  - Automatic signature verification during license validation when signed mode is enabled
- **Key Generation Artisan Command**: `php artisan licensing:keys`
  - Generate Ed25519 public/private key pairs for signed license verification
  - `--force` option to overwrite existing keys
  - `--show` option to display generated keys in console
  - `--write` option to automatically append keys to `.env` file
- **License Management CLI Commands**:
  - `licensing:list` - List licenses with filtering (product, status, owner, pagination)
  - `licensing:show` - Display detailed license information (by key or ID, with `--full` option)
  - `licensing:create` - Interactive license creation with validation
  - `licensing:revoke` - Revoke licenses by key or ID (with `--force` option)
  - `licensing:activate` - Activate licenses by key or ID
  - `licensing:deactivate` - Deactivate license bindings
  - `licensing:stats` - Display license and activation statistics
- **LicenseManager Enhancements**:
  - `list()` method for querying licenses with filters and pagination
  - `getStatistics()` method for license and activation statistics
- **Utility Classes**:
  - `LicenseKeyHelper` utility class with `mask()` and `resolveLicense()` methods
- **Configuration Updates**:
  - `license_generation` config option (`'random'` | `'signed'`)
  - `signature.public_key` and `signature.private_key` config options
- **New Exception**: `InvalidSignatureException` for signature verification failures

### Changed
- License key generation now uses strategy pattern via `LicenseGeneratorInterface`
- `KeyGeneratorContract` and `KeyGenerator` marked as deprecated (maintained for backward compatibility)
- License validation now performs signature verification when using signed generation mode

### Security
- Ed25519 signature-based licenses provide tamper-resistant validation
- Private keys should never be committed to version control or distributed with client applications
- Public keys can be embedded in client applications for offline verification

[1.1.0]: https://github.com/devravik/laravel-licensing/releases/tag/1.1.0

## [1.0.0] - 2024-01-01

### Added
- Cryptographically secure license key generation via `random_bytes()`
- Hashed license key storage using Laravel's `Hash` facade (bcrypt) with a SHA-256 `lookup_token` for O(log n) pre-filtering
- Fluent `LicenseBuilder` for creating licenses with product, seat, and expiry configuration
- Seat-based activation system with configurable limits per license
- `License::validate()` — validates a raw key and throws typed exceptions on failure
- `License::activate()` — binds a license to a domain, IP, machine ID, or custom identifier
- `License::deactivate()` — removes an activation binding to free a seat
- `License::revoke()` — permanently revokes a license
- `License::find()` — retrieves a license by raw key without throwing on failure
- Grace period support — configurable days of temporary validity after expiration
- `HasLicenses` trait for polymorphic license ownership on any Eloquent model
- `LicenseCreated`, `LicenseActivated`, `LicenseDeactivated`, `LicenseRevoked`, `LicenseExpired` events
- `CheckLicense` middleware — protects routes by product name (`license:pro`)
- `CheckValidLicense` middleware — protects routes requiring any valid license (`license.valid`)
- `LicenseManagerException` base exception with typed subclasses: `InvalidLicenseException`, `LicenseExpiredException`, `LicenseRevokedException`, `SeatLimitExceededException`, `LicenseAlreadyActivatedException`
- `license:status` Artisan command for verifying package configuration
- Publishable config (`config/license.php`) and migrations
- Full test suite covering feature and unit scenarios across MySQL and SQLite
- Support for Laravel 10, 11, and 12 with PHP 8.1 (Laravel 10 only), 8.2, and 8.3

[1.0.0]: https://github.com/devravik/laravel-licensing/releases/tag/1.0.0
