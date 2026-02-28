<?php

namespace DevRavik\LaravelLicensing\Console;

use DevRavik\LaravelLicensing\Contracts\LicenseContract;
use DevRavik\LaravelLicensing\Contracts\LicenseManagerContract;
use DevRavik\LaravelLicensing\Support\LicenseKeyHelper;
use Illuminate\Console\Command;

class ShowLicenseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'licensing:show
                            {--key= : License key}
                            {--id= : License ID}
                            {--full : Show full license key (unmasked)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display detailed information about a specific license';

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

        // If --key was provided, use it for display (it's the raw key)
        // Otherwise, use the key from the license (which may be hashed)
        /** @var \DevRavik\LaravelLicensing\Models\License $license */
        $displayKey = $this->option('key') ?: ($license->key ?? 'N/A');

        $this->line('<options=bold>License Details</>');
        $this->line(str_repeat('─', 60));
        $this->newLine();

        // Basic Information
        $this->line('<options=bold>Basic Information</>');
        $this->line("  ID: {$license->id}");
        $this->line("  Product: {$license->product}");
        $this->line('  Key: '.($this->option('full') ? $displayKey : LicenseKeyHelper::mask($displayKey)));
        $this->newLine();

        // Owner Information
        $this->line('<options=bold>Owner</>');
        $owner = $license->owner;
        if ($owner) {
            $this->line("  Type: {$license->owner_type}");
            $this->line("  ID: {$license->owner_id}");
            if (is_object($owner)) {
                if (method_exists($owner, 'name') && property_exists($owner, 'name')) {
                    /** @var object{name: string} $owner */
                    $this->line("  Name: {$owner->name}");
                }
                if (method_exists($owner, 'email') && property_exists($owner, 'email')) {
                    /** @var object{email: string} $owner */
                    $this->line("  Email: {$owner->email}");
                }
            }
        } else {
            $this->line("  Type: {$license->owner_type}");
            $this->line("  ID: {$license->owner_id}");
            $this->line('  <fg=yellow>Owner model not found</>');
        }
        $this->newLine();

        // Seats Information
        $this->line('<options=bold>Seats</>');
        $this->line("  Total: {$license->seats}");
        $this->line("  Used: {$license->countActivations()}");
        $this->line("  Available: {$license->seatsRemaining()}");
        $this->newLine();

        // Status Information
        $this->line('<options=bold>Status</>');
        $status = $this->formatStatus($license);
        $this->line("  Status: {$status}");

        if ($license->expires_at) {
            $this->line("  Expires: {$license->expires_at->format('Y-m-d H:i:s')}");
            if ($license->isExpired() && $license->isInGracePeriod()) {
                $this->line("  Grace Period: {$license->graceDaysRemaining()} days remaining");
            }
        } else {
            $this->line('  Expires: Never');
        }

        if ($license->revoked_at) {
            $this->line("  Revoked: {$license->revoked_at->format('Y-m-d H:i:s')}");
        }
        $this->newLine();

        // Activations
        $activations = $license->activations;
        if ($activations->isNotEmpty()) {
            $this->line('<options=bold>Activations</>');
            foreach ($activations as $activation) {
                /** @var \DevRavik\LaravelLicensing\Models\Activation $activation */
                $this->line("  • {$activation->binding} (activated: {$activation->activated_at->format('Y-m-d H:i:s')})");
            }
        } else {
            $this->line('<options=bold>Activations</>');
            $this->line('  <fg=yellow>No activations</>');
        }
        $this->newLine();

        // Timestamps
        $this->line('<options=bold>Timestamps</>');
        $this->line("  Created: {$license->created_at->format('Y-m-d H:i:s')}");
        $this->line("  Updated: {$license->updated_at->format('Y-m-d H:i:s')}");

        return self::SUCCESS;
    }

    /**
     * Format license status with color coding.
     */
    protected function formatStatus(LicenseContract $license): string
    {
        if ($license->isRevoked()) {
            return '<fg=red>Revoked</>';
        }

        if ($license->isExpired()) {
            if ($license->isInGracePeriod()) {
                return '<fg=yellow>Expired (Grace Period)</>';
            }

            return '<fg=yellow>Expired</>';
        }

        return '<fg=green>Active</>';
    }
}
