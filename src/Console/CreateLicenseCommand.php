<?php

namespace DevRavik\LaravelLicensing\Console;

use DevRavik\LaravelLicensing\Contracts\LicenseManagerContract;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class CreateLicenseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'licensing:create
                            {--owner-type= : Owner model class (e.g., App\\Models\\User)}
                            {--owner-id= : Owner model ID}
                            {--product= : Product name or tier}
                            {--seats= : Number of seats (default: 1)}
                            {--expires-days= : Number of days until expiration}
                            {--non-interactive : Skip interactive prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new license interactively or via options';

    /**
     * Execute the console command.
     */
    public function handle(LicenseManagerContract $manager): int
    {
        $nonInteractive = $this->option('non-interactive');

        // Get owner
        $ownerType = $this->option('owner-type');
        $ownerId = $this->option('owner-id');

        if (! $nonInteractive && (! $ownerType || ! $ownerId)) {
            $ownerType = $this->ask('Owner model class (e.g., App\\Models\\User)', $ownerType);
            $ownerId = $this->ask('Owner model ID', $ownerId);
        }

        if (! $ownerType || ! $ownerId) {
            $this->error('Owner type and ID are required.');

            return self::FAILURE;
        }

        if (! class_exists($ownerType)) {
            $this->error("Class '{$ownerType}' does not exist. Owner not found.");

            return self::FAILURE;
        }

        /** @var Model|null $owner */
        $owner = $ownerType::find($ownerId);

        if ($owner === null) {
            $this->error("Owner with ID {$ownerId} not found.");

            return self::FAILURE;
        }

        // Get product
        $product = $this->option('product');
        if (! $nonInteractive && ! $product) {
            $product = $this->ask('Product name or tier', $product ?? '');
        }

        if (! $product) {
            $this->error('Product is required.');

            return self::FAILURE;
        }

        // Get seats
        $seats = $this->option('seats');
        if (! $nonInteractive && ! $seats) {
            $seatsInput = $this->ask('Number of seats', '1');
            $seats = $seatsInput ? (int) $seatsInput : 1;
        }
        $seats = (int) ($seats ?? 1);

        if ($seats < 1) {
            $this->error('Seats must be at least 1.');

            return self::FAILURE;
        }

        // Get expiry
        $expiresDays = $this->option('expires-days');
        if (! $nonInteractive && ! $expiresDays) {
            $expiresDays = $this->ask('Days until expiration (leave empty for never)', null);
        }

        // Build license
        $builder = $manager->for($owner)
            ->product($product)
            ->seats($seats);

        if ($expiresDays) {
            $builder->expiresInDays((int) $expiresDays);
        }

        try {
            $license = $builder->create();

            /** @var \DevRavik\LaravelLicensing\Models\License $license */
            $this->newLine();
            $this->info('✓ License created successfully!');
            $this->newLine();
            $this->line('<options=bold>License Information</>');
            $this->line("  ID: {$license->id}");
            $this->line("  Product: {$license->product}");
            $this->line("  Seats: {$license->seats}");
            $this->line('  Expires: '.($license->expires_at ? $license->expires_at->format('Y-m-d H:i:s') : 'Never'));
            $this->newLine();
            $this->warn('⚠️  IMPORTANT: Save this license key now. It will not be shown again.');
            $this->newLine();
            $this->line('<options=bold>License Key:</>');
            $this->line($license->key);
            $this->newLine();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to create license: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
