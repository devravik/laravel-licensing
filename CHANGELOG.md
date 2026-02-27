# Changelog

All notable changes to `devravik/laravel-licensing` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
- Support for Laravel 10 and 11 with PHP 8.1, 8.2, and 8.3

[1.0.0]: https://github.com/devravik/laravel-licensing/releases/tag/1.0.0
