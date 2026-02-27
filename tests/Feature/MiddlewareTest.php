<?php

namespace DevRavik\LaravelLicensing\Tests\Feature;

use DevRavik\LaravelLicensing\Facades\License;
use DevRavik\LaravelLicensing\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class MiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Register test routes using the middleware aliases.
        Route::get('/test/pro', fn () => 'ok')->middleware('license:pro');
        Route::get('/test/any', fn () => 'ok')->middleware('license.valid');
    }

    public function test_valid_pro_license_passes_check_license_middleware(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->create();

        $this->getJson('/test/pro', ['X-License-Key' => $license->key])
            ->assertOk()
            ->assertSee('ok');
    }

    public function test_wrong_product_is_denied_by_check_license_middleware(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('basic')->create();

        $this->getJson('/test/pro', ['X-License-Key' => $license->key])
            ->assertForbidden();
    }

    public function test_missing_key_returns_401(): void
    {
        $this->getJson('/test/pro')
            ->assertUnauthorized();
    }

    public function test_invalid_key_returns_404(): void
    {
        $this->getJson('/test/pro', ['X-License-Key' => 'totally-fake-key'])
            ->assertStatus(404);
    }

    public function test_valid_any_license_passes_check_valid_license_middleware(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('basic')->create();

        $this->getJson('/test/any', ['X-License-Key' => $license->key])
            ->assertOk();
    }

    public function test_missing_key_on_any_route_returns_401(): void
    {
        $this->getJson('/test/any')
            ->assertUnauthorized();
    }

    public function test_license_key_can_be_passed_as_query_parameter(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->create();

        $this->getJson("/test/pro?license_key={$license->key}")
            ->assertOk();
    }

    public function test_revoked_license_is_denied_by_middleware(): void
    {
        $user = $this->createUser();
        $license = License::for($user)->product('pro')->create();
        $key = $license->key;

        License::revoke($key);

        $this->getJson('/test/pro', ['X-License-Key' => $key])
            ->assertForbidden();
    }
}
