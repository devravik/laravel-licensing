<?php

namespace DevRavik\LaravelLicensing\Console;

use DevRavik\LaravelLicensing\Contracts\LicenseManagerContract;
use Illuminate\Console\Command;

class LicenseStatisticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'licensing:stats
                            {--product= : Filter statistics by product}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display license and activation statistics';

    /**
     * Execute the console command.
     */
    public function handle(LicenseManagerContract $manager): int
    {
        $product = $this->option('product');

        $stats = $manager->getStatistics($product);

        $this->newLine();
        $this->line('<options=bold>License Statistics</>');
        if ($product) {
            $this->line("  Product: {$product}");
        }
        $this->line(str_repeat('─', 60));
        $this->newLine();

        // License Counts
        $this->line('<options=bold>Licenses</>');
        $this->line("  Total: {$stats['total_licenses']}");
        $this->line("  Active: <fg=green>{$stats['active_licenses']}</>");
        $this->line("  Expired: <fg=yellow>{$stats['expired_licenses']}</>");
        $this->line("  Revoked: <fg=red>{$stats['revoked_licenses']}</>");
        $this->newLine();

        // Seat Information
        $this->line('<options=bold>Seats</>');
        $this->line("  Total: {$stats['total_seats']}");
        $this->line("  Used: {$stats['used_seats']}");
        $this->line("  Available: <fg=green>{$stats['available_seats']}</>");
        $this->newLine();

        // Activations
        $this->line('<options=bold>Activations</>');
        $this->line("  Total: {$stats['total_activations']}");
        $this->newLine();

        // Usage Percentage
        if ($stats['total_seats'] > 0) {
            $usagePercent = round(($stats['used_seats'] / $stats['total_seats']) * 100, 1);
            $this->line('<options=bold>Usage</>');
            $this->line("  Seat Usage: {$usagePercent}%");
            $this->newLine();
        }

        return self::SUCCESS;
    }
}
