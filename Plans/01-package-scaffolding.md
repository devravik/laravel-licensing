# Plan 01: Package Scaffolding & Composer Setup

## Objective

Establish the complete skeleton of the `devravik/laravel-licensing` Composer package: directory layout, `composer.json`, autoloading, stub files, and the root-level supporting files expected by any professional open-source package.

---

## 1. Final Directory Structure

```
laravel-licensing/
├── config/
│   └── license.php
├── database/
│   └── migrations/
│       ├── 2024_01_01_000001_create_licenses_table.php
│       └── 2024_01_01_000002_create_license_activations_table.php
├── src/
│   ├── Contracts/
│   │   ├── LicenseContract.php
│   │   └── ActivationContract.php
│   ├── Events/
│   │   ├── LicenseCreated.php
│   │   ├── LicenseActivated.php
│   │   ├── LicenseDeactivated.php
│   │   ├── LicenseRevoked.php
│   │   └── LicenseExpired.php
│   ├── Exceptions/
│   │   ├── LicenseManagerException.php
│   │   ├── InvalidLicenseException.php
│   │   ├── LicenseExpiredException.php
│   │   ├── LicenseRevokedException.php
│   │   ├── SeatLimitExceededException.php
│   │   └── LicenseAlreadyActivatedException.php
│   ├── Facades/
│   │   └── License.php
│   ├── Http/
│   │   └── Middleware/
│   │       ├── CheckLicense.php
│   │       └── CheckValidLicense.php
│   ├── Models/
│   │   ├── License.php
│   │   └── Activation.php
│   ├── Support/
│   │   └── HasLicenses.php
│   ├── KeyGenerator.php
│   ├── LicenseBuilder.php
│   ├── LicenseManager.php
│   ├── LicenseValidator.php
│   └── LicenseServiceProvider.php
├── tests/
│   ├── Feature/
│   │   ├── LicenseCreationTest.php
│   │   ├── LicenseValidationTest.php
│   │   ├── LicenseActivationTest.php
│   │   ├── LicenseRevocationTest.php
│   │   └── MiddlewareTest.php
│   ├── Unit/
│   │   ├── KeyGeneratorTest.php
│   │   └── LicenseValidatorTest.php
│   └── TestCase.php
├── .github/
│   └── workflows/
│       ├── tests.yml
│       └── static-analysis.yml
├── composer.json
├── phpunit.xml
├── phpstan.neon
├── .gitignore
├── CHANGELOG.md
├── LICENSE
├── SECURITY.md
└── README.md
```

---

## 2. `composer.json`

This is the single most important file for a publishable Packagist package. Every field must be set correctly before the first tag is pushed.

```json
{
    "name": "devravik/laravel-licensing",
    "description": "A production-ready Laravel package for generating, managing, activating, and validating software licenses.",
    "keywords": [
        "laravel",
        "license",
        "licensing",
        "activation",
        "seat-based",
        "software-license"
    ],
    "homepage": "https://github.com/devravik/laravel-licensing",
    "license": "MIT",
    "authors": [
        {
            "name": "Ravi K Gupta",
            "email": "dev.ravikgupt@gmail.com",
            "role": "Developer"
        }
    ],
    "require": {
        "php": "^8.1",
        "illuminate/contracts": "^10.0|^11.0|^12.0",
        "illuminate/database": "^10.0|^11.0|^12.0",
        "illuminate/events": "^10.0|^11.0|^12.0",
        "illuminate/hashing": "^10.0|^11.0|^12.0",
        "illuminate/support": "^10.0|^11.0|^12.0"
    },
    "require-dev": {
        "orchestra/testbench": "^8.0|^9.0",
        "phpunit/phpunit": "^10.0|^11.0",
        "larastan/larastan": "^2.0",
        "laravel/pint": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "DevRavik\\LaravelLicensing\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "DevRavik\\LaravelLicensing\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "DevRavik\\LaravelLicensing\\LicenseServiceProvider"
            ],
            "aliases": {
                "License": "DevRavik\\LaravelLicensing\\Facades\\License"
            }
        }
    },
    "scripts": {
        "test": "vendor/bin/phpunit",
        "test-coverage": "vendor/bin/phpunit --coverage-html coverage",
        "analyse": "vendor/bin/phpstan analyse",
        "format": "vendor/bin/pint"
    },
    "config": {
        "sort-packages": true
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

### Key Decisions

| Field | Value | Reason |
|-------|-------|--------|
| `name` | `devravik/laravel-licensing` | Matches Packagist profile `devravik` |
| `require` → `illuminate/*` | `^10.0\|^11.0\|^12.0` | Supports Laravel 10, 11, and 12 |
| `extra.laravel` | providers + aliases | Enables Laravel auto-discovery — no manual registration needed |
| `minimum-stability` | `dev` | Allows dev dependencies during development; switch to `stable` before 1.0 tag |

---

## 3. `phpunit.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>

    <coverage>
        <include>
            <directory suffix=".php">src</directory>
        </include>
    </coverage>

    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
    </php>
</phpunit>
```

---

## 4. `phpstan.neon`

Static analysis is expected by the community for serious packages.

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - src
    level: 6
    checkMissingIterableValueType: false
```

---

## 5. `.gitignore`

```gitignore
/vendor/
/coverage/
/.phpunit.cache/
/.idea/
/.vscode/
composer.lock
.env
```

---

## 6. `config/license.php` (stub)

This file is published to the consuming application via `vendor:publish`. Detailed content is covered in Plan 06, but the file must exist in the package from the start.

```php
<?php

return [

    'license_model' => \DevRavik\LaravelLicensing\Models\License::class,

    'activation_model' => \DevRavik\LaravelLicensing\Models\Activation::class,

    'key_length' => env('LICENSE_KEY_LENGTH', 32),

    'hash_keys' => env('LICENSE_HASH_KEYS', true),

    'default_expiry_days' => env('LICENSE_DEFAULT_EXPIRY_DAYS', 365),

    'grace_period_days' => env('LICENSE_GRACE_PERIOD_DAYS', 7),

];
```

---

## 7. `CHANGELOG.md` (initial stub)

```markdown
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial package structure and scaffolding.
```

---

## 8. `SECURITY.md` (stub)

```markdown
# Security Policy

## Reporting a Vulnerability

Please do NOT report security vulnerabilities via GitHub Issues.

Instead, email `dev.ravikgupt@gmail.com` with the subject line:
`[SECURITY] devravik/laravel-licensing - <brief description>`

You will receive a response within 48 hours. If the issue is confirmed,
a fix will be released and a security advisory will be published.
```

---

## 9. `LICENSE` (MIT)

```
MIT License

Copyright (c) 2024 Ravi K Gupta

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## 10. Bootstrap `tests/TestCase.php`

This base test case is the foundation all Feature and Unit tests extend. Uses `orchestra/testbench` to boot a real Laravel application in isolation.

```php
<?php

namespace DevRavik\LaravelLicensing\Tests;

use DevRavik\LaravelLicensing\LicenseServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            LicenseServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}
```

---

## 11. Execution Checklist

- [ ] Create all directories listed in the directory structure
- [ ] Write `composer.json` with correct namespace, autoloading, and `extra.laravel`
- [ ] Write `phpunit.xml` pointing at `tests/` with in-memory SQLite
- [ ] Write `phpstan.neon` at level 6
- [ ] Write `.gitignore`
- [ ] Add stub `config/license.php`
- [ ] Add stub `CHANGELOG.md`, `SECURITY.md`, and `LICENSE`
- [ ] Add `tests/TestCase.php` using `orchestra/testbench`
- [ ] Run `composer install` and confirm autoloading resolves
- [ ] Initialize a local Git repository and make the initial commit

---

## 12. Dependencies Between Plans

This plan is **Plan 1 — a prerequisite for all other plans**. No other plan can begin until the directory structure and `composer.json` are in place. After completing this plan:

- Plan 02 can create migration files in `database/migrations/` and model files in `src/Models/`
- Plan 06 can register the service provider defined in `src/LicenseServiceProvider.php`
- Plan 10 can configure GitHub Actions using `.github/workflows/`
