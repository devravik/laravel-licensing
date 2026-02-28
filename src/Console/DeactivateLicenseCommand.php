<?php

namespace DevRavik\LaravelLicensing\Console;

use DevRavik\LaravelLicensing\Contracts\LicenseManagerContract;
use DevRavik\LaravelLicensing\Support\LicenseKeyHelper;
use Illuminate\Console\Command;

class DeactivateLicenseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'licensing:deactivate
                            {--key= : License key}
                            {--id= : License ID}
                            {--binding= : Activation binding to remove}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate a license binding';

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

        $binding = $this->option('binding');

        if (! $binding) {
            // Show available activations
            /** @var \DevRavik\LaravelLicensing\Models\License $license */
            $activations = $license->activations;
            if ($activations->isEmpty()) {
                $this->warn('No activations found for this license.');

                return self::SUCCESS;
            }

            $this->line('<options=bold>Available Activations</>');
            $choices = [];
            foreach ($activations as $activation) {
                /** @var \DevRavik\LaravelLicensing\Models\Activation $activation */
                $choices[] = $activation->binding;
                $this->line("  • {$activation->binding}");
            }
            $this->newLine();

            $binding = $this->choice('Select binding to deactivate', $choices);
        }

        // Use the key from option if provided (raw key)
        // If resolving by ID, we can't deactivate without the raw key
        $key = $this->option('key');
        if (! $key) {
            $this->error('License key is required for deactivation. Use --key to provide the raw license key.');

            return self::FAILURE;
        }

        try {
            $result = $manager->deactivate($key, $binding);

            if ($result) {
                $this->newLine();
                $this->info('✓ Activation deactivated successfully!');
                $this->newLine();
                /** @var \DevRavik\LaravelLicensing\Models\License $license */
                $this->line('<options=bold>Deactivation Details</>');
                $this->line("  License ID: {$license->id}");
                $this->line("  Product: {$license->product}");
                $this->line("  Binding: {$binding}");
                $freshLicense = $license->fresh();
                $this->line("  Seats Used: {$freshLicense->countActivations()} / {$license->seats}");
                $this->newLine();
            } else {
                $this->warn('Binding not found or already deactivated.');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to deactivate license: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
