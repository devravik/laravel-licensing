<?php

namespace DevRavik\LaravelLicensing\Tests\Unit;

use Carbon\Carbon;
use DevRavik\LaravelLicensing\Contracts\LicenseContract;
use DevRavik\LaravelLicensing\Services\LicenseBuilder;
use DevRavik\LaravelLicensing\Services\LicenseManager;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use Orchestra\Testbench\TestCase;

class LicenseBuilderTest extends TestCase
{
    /** A minimal anonymous Eloquent model used as the license owner. */
    private Model $owner;

    /** @var \Mockery\MockInterface&LicenseManager */
    private LicenseManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        // Anonymous concrete model (no DB calls needed for builder tests).
        $this->owner = new class extends Model
        {
            protected $primaryKey = 'id';

            public $exists = true;

            protected $attributes = ['id' => 1];
        };

        $this->manager = Mockery::mock(LicenseManager::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        // No service provider needed — the builder only requires config() helpers,
        // which we seed manually in getEnvironmentSetUp(). Registering the service
        // provider would cause it to call loadMigrationsFrom() which requires a DB.
        return [];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Seed the config values that LicenseBuilder::create() reads.
        config()->set('license.default_expiry_days', 365);
    }

    private function builder(): LicenseBuilder
    {
        return new LicenseBuilder($this->owner, $this->manager);
    }

    // -------------------------------------------------------------------------
    // product()
    // -------------------------------------------------------------------------

    public function test_product_returns_the_builder_for_chaining(): void
    {
        $builder = $this->builder();
        $this->assertSame($builder, $builder->product('pro'));
    }

    // -------------------------------------------------------------------------
    // seats()
    // -------------------------------------------------------------------------

    public function test_seats_returns_the_builder_for_chaining(): void
    {
        $builder = $this->builder();
        $this->assertSame($builder, $builder->seats(3));
    }

    public function test_seats_rejects_zero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/at least 1/');
        $this->builder()->seats(0);
    }

    public function test_seats_rejects_negative_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder()->seats(-5);
    }

    // -------------------------------------------------------------------------
    // expiresInDays()
    // -------------------------------------------------------------------------

    public function test_expires_in_days_returns_the_builder_for_chaining(): void
    {
        $builder = $this->builder();
        $this->assertSame($builder, $builder->expiresInDays(30));
    }

    public function test_expires_in_days_rejects_zero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/at least 1/');
        $this->builder()->expiresInDays(0);
    }

    public function test_expires_in_days_rejects_negative_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder()->expiresInDays(-1);
    }

    // -------------------------------------------------------------------------
    // expiresAt()
    // -------------------------------------------------------------------------

    public function test_expires_at_returns_the_builder_for_chaining(): void
    {
        $builder = $this->builder();
        $future = Carbon::now()->addDays(30);
        $this->assertSame($builder, $builder->expiresAt($future));
    }

    public function test_expires_at_rejects_a_past_date(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/future/');
        $this->builder()->expiresAt(Carbon::now()->subDay());
    }

    // -------------------------------------------------------------------------
    // create() — guard: product must be set
    // -------------------------------------------------------------------------

    public function test_create_throws_when_product_is_not_set(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/product must be set/');
        $this->builder()->create();
    }

    // -------------------------------------------------------------------------
    // create() — delegates to LicenseManager with correct arguments
    // -------------------------------------------------------------------------

    public function test_create_delegates_to_manager_with_explicit_expiry(): void
    {
        $future = Carbon::now()->addDays(30);
        $license = Mockery::mock(LicenseContract::class);

        $this->manager
            ->shouldReceive('createLicense')
            ->once()
            ->with($this->owner, 'pro', 3, Mockery::on(
                fn ($arg) => $arg instanceof Carbon && $arg->isSameDay($future)
            ))
            ->andReturn($license);

        $result = $this->builder()
            ->product('pro')
            ->seats(3)
            ->expiresAt($future)
            ->create();

        $this->assertSame($license, $result);
    }

    public function test_create_uses_config_default_expiry_when_none_is_set(): void
    {
        config(['license.default_expiry_days' => 365]);

        $license = Mockery::mock(LicenseContract::class);

        $expectedExpiry = Carbon::now()->addDays(365);

        $this->manager
            ->shouldReceive('createLicense')
            ->once()
            ->with(
                $this->owner,
                'basic',
                1,
                Mockery::on(fn ($arg) => $arg instanceof Carbon && $arg->isSameDay($expectedExpiry))
            )
            ->andReturn($license);

        $result = $this->builder()->product('basic')->create();

        $this->assertSame($license, $result);
    }

    public function test_create_passes_null_expiry_when_config_default_is_null(): void
    {
        config(['license.default_expiry_days' => null]);

        $license = Mockery::mock(LicenseContract::class);

        $this->manager
            ->shouldReceive('createLicense')
            ->once()
            ->with($this->owner, 'enterprise', 1, null)
            ->andReturn($license);

        $result = $this->builder()->product('enterprise')->create();

        $this->assertSame($license, $result);
    }

    public function test_builder_expiry_takes_precedence_over_config_default(): void
    {
        config(['license.default_expiry_days' => 365]);

        $explicitExpiry = Carbon::now()->addDays(7);
        $license = Mockery::mock(LicenseContract::class);

        $this->manager
            ->shouldReceive('createLicense')
            ->once()
            ->with(
                $this->owner,
                'pro',
                1,
                Mockery::on(fn ($arg) => $arg instanceof Carbon && $arg->isSameDay($explicitExpiry))
            )
            ->andReturn($license);

        $result = $this->builder()
            ->product('pro')
            ->expiresInDays(7)
            ->create();

        $this->assertSame($license, $result);
    }

    // -------------------------------------------------------------------------
    // toArray()
    // -------------------------------------------------------------------------

    public function test_to_array_returns_expected_keys(): void
    {
        $state = $this->builder()->product('pro')->seats(5)->toArray();

        $this->assertArrayHasKey('owner_type', $state);
        $this->assertArrayHasKey('owner_id', $state);
        $this->assertArrayHasKey('product', $state);
        $this->assertArrayHasKey('seats', $state);
        $this->assertArrayHasKey('expires_at', $state);
    }

    public function test_to_array_reflects_configured_values(): void
    {
        $future = Carbon::now()->addDays(10);

        $state = $this->builder()
            ->product('starter')
            ->seats(2)
            ->expiresAt($future)
            ->toArray();

        $this->assertSame('starter', $state['product']);
        $this->assertSame(2, $state['seats']);
        $this->assertSame(1, $state['owner_id']);
        $this->assertNotNull($state['expires_at']);
    }

    public function test_to_array_expires_at_is_null_when_not_set(): void
    {
        $state = $this->builder()->product('pro')->toArray();
        $this->assertNull($state['expires_at']);
    }
}
