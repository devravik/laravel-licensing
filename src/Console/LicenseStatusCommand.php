<?php

namespace DevRavik\LaravelLicensing\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LicenseStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'license:status';

    /**
     * The console command description.
     */
    protected $description = 'Display the current status of the Laravel Licensing package configuration and database tables';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->line('<fg=cyan;options=bold>Laravel Licensing — Package Status</>');
        $this->line(str_repeat('─', 50));
        $this->newLine();

        // ── Configuration ───────────────────────────────────────────────────
        $this->line('<options=bold>Configuration</>');

        $config = [
            ['Key Length',           config('license.key_length', 32)],
            ['Hash Keys',            config('license.hash_keys', true) ? '<fg=green>enabled</>' : '<fg=yellow>disabled (plaintext — not recommended for production)</>'],
            ['Default Expiry (days)',config('license.default_expiry_days') ?? '<fg=yellow>null (licenses never expire by default)</>'],
            ['Grace Period (days)',  config('license.grace_period_days', 0)],
            ['License Model',       config('license.license_model')],
            ['Activation Model',    config('license.activation_model')],
        ];

        $this->table(['Setting', 'Value'], $config);

        $this->newLine();

        // ── Database Tables ──────────────────────────────────────────────────
        $this->line('<options=bold>Database Tables</>');

        $licensesExist    = Schema::hasTable('licenses');
        $activationsExist = Schema::hasTable('license_activations');

        $tables = [
            [
                'licenses',
                $licensesExist ? '<fg=green>exists</>' : '<fg=red>missing — run: php artisan migrate</>',
                $licensesExist ? number_format(DB::table('licenses')->count()) : '—',
            ],
            [
                'license_activations',
                $activationsExist ? '<fg=green>exists</>' : '<fg=red>missing — run: php artisan migrate</>',
                $activationsExist ? number_format(DB::table('license_activations')->count()) : '—',
            ],
        ];

        $this->table(['Table', 'Status', 'Records'], $tables);

        $this->newLine();

        if (! $licensesExist || ! $activationsExist) {
            $this->warn('One or more required database tables are missing.');
            $this->line('Run <fg=cyan>php artisan vendor:publish --tag=license-migrations</> then <fg=cyan>php artisan migrate</> to create them.');
            $this->newLine();

            return self::FAILURE;
        }

        $this->info('Package is installed and configured correctly.');
        $this->newLine();

        return self::SUCCESS;
    }
}
