<?php

namespace DevRavik\LaravelLicensing\Console;

use DevRavik\LaravelLicensing\Contracts\LicenseManagerContract;
use DevRavik\LaravelLicensing\Events\LicenseActivated;
use DevRavik\LaravelLicensing\Exceptions\LicenseAlreadyActivatedException;
use DevRavik\LaravelLicensing\Exceptions\SeatLimitExceededException;
use DevRavik\LaravelLicensing\Services\LicenseValidator;
use DevRavik\LaravelLicensing\Support\LicenseKeyHelper;
use Illuminate\Console\Command;

class ActivateLicenseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'licensing:activate
                            {--key= : License key}
                            {--id= : License ID}
                            {--binding= : Activation binding (domain, IP, machine ID, etc.)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate a license for a binding';

    /**
     * Execute the console command.
     */
    public function handle(LicenseManagerContract $manager, LicenseValidator $validator): int
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
            $binding = $this->ask('Activation binding (domain, IP, machine ID, etc.)');
        }

        if (! $binding) {
            $this->error('Binding is required.');

            return self::FAILURE;
        }

        // Use the key from option if provided (raw key)
        // If resolving by ID, we can activate directly using the license object
        $key = $this->option('key');
        
        try {
            if ($key) {
                // Activate using raw key (validates the license)
                $activation = $manager->activate($key, $binding);
            } else {
                // Activate by ID - validate the license first
                $validator->assertValid($license);
                
                // Use reflection to access protected createActivation method
                // or create activation directly
                $activationModelClass = config('license.activation_model');
                
                // Check for duplicate binding
                $existing = $license->activations()
                    ->where('binding', $binding)
                    ->first();
                
                if ($existing !== null) {
                    throw LicenseAlreadyActivatedException::forBinding($license, $binding);
                }
                
                // Check seat availability
                if (! $license->hasAvailableSeat()) {
                    throw SeatLimitExceededException::forLicense($license);
                }
                
                /** @var \DevRavik\LaravelLicensing\Models\Activation $activation */
                $activation = new $activationModelClass;
                $activation->license_id = $license->getKey();
                $activation->binding = $binding;
                $activation->activated_at = now();
                $activation->save();
                
                // Dispatch event
                event(new LicenseActivated($license, $activation));
            }

            $this->newLine();
            $this->info('✓ License activated successfully!');
            $this->newLine();
            $this->line('<options=bold>Activation Details</>');
            $this->line("  License ID: {$license->id}");
            $this->line("  Product: {$license->product}");
            $this->line("  Binding: {$activation->binding}");
            $this->line("  Activated: {$activation->activated_at->format('Y-m-d H:i:s')}");
            $this->line("  Seats Used: {$license->countActivations()} / {$license->seats}");
            $this->newLine();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to activate license: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
