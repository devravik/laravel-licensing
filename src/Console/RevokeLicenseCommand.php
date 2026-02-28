<?php

namespace DevRavik\LaravelLicensing\Console;

use DevRavik\LaravelLicensing\Contracts\LicenseManagerContract;
use DevRavik\LaravelLicensing\Support\LicenseKeyHelper;
use Illuminate\Console\Command;

class RevokeLicenseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'licensing:revoke
                            {--key= : License key}
                            {--id= : License ID}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revoke a license by key or ID';

    /**
     * Execute the console command.
     */
    public function handle(LicenseManagerContract $manager): int
    {
        $keyOrId = $this->option('key') ?? $this->option('id');

        if (! $keyOrId) {
            $this->error('Either --key or --id must be provided.');

            return self::FAILURE;
        }

        $license = LicenseKeyHelper::resolveLicense($keyOrId, $manager);

        if ($license === null) {
            $this->error('License not found.');

            return self::FAILURE;
        }

        if ($license->isRevoked()) {
            $this->warn('License is already revoked.');

            return self::SUCCESS;
        }

        // Show license info
        /** @var \DevRavik\LaravelLicensing\Models\License $license */
        $this->line('<options=bold>License to Revoke</>');
        $this->line("  ID: {$license->id}");
        $this->line("  Product: {$license->product}");
        $this->line('  Key: '.LicenseKeyHelper::mask($license->key ?? 'N/A'));
        $this->newLine();

        // Confirmation
        if (! $this->option('force')) {
            if (! $this->confirm('Are you sure you want to revoke this license?', false)) {
                $this->info('Revocation cancelled.');

                return self::SUCCESS;
            }
        }

        try {
            // Use the key from option if provided (raw key), otherwise use from license
            // If resolving by ID, we can't get the raw key, so update directly
            $key = $this->option('key');
            if ($key) {
                $manager->revoke($key);
            } else {
                // Revoking by ID - update directly since we don't have raw key
                /** @var \DevRavik\LaravelLicensing\Models\License $license */
                $license->revoked_at = now();
                $license->save();
            }

            $this->info('✓ License revoked successfully.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to revoke license: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
