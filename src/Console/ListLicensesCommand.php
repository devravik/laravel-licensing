<?php

namespace DevRavik\LaravelLicensing\Console;

use DevRavik\LaravelLicensing\Contracts\LicenseContract;
use DevRavik\LaravelLicensing\Contracts\LicenseManagerContract;
use DevRavik\LaravelLicensing\Support\LicenseKeyHelper;
use Illuminate\Console\Command;

class ListLicensesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'licensing:list
                            {--product= : Filter by product name}
                            {--owner-type= : Filter by owner type (model class)}
                            {--owner-id= : Filter by owner ID}
                            {--status= : Filter by status (active|expired|revoked)}
                            {--expired : Show only expired licenses}
                            {--revoked : Show only revoked licenses}
                            {--per-page=15 : Number of licenses per page}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all licenses with optional filtering';

    /**
     * Execute the console command.
     */
    public function handle(LicenseManagerContract $manager): int
    {
        $filters = [];

        if ($this->option('product')) {
            $filters['product'] = $this->option('product');
        }

        if ($this->option('owner-type')) {
            $filters['owner_type'] = $this->option('owner-type');
        }

        if ($this->option('owner-id')) {
            $filters['owner_id'] = (int) $this->option('owner-id');
        }

        if ($this->option('status')) {
            $filters['status'] = $this->option('status');
        }

        if ($this->option('expired')) {
            $filters['expired'] = true;
        }

        if ($this->option('revoked')) {
            $filters['revoked'] = true;
        }

        if ($this->option('per-page')) {
            $filters['per_page'] = (int) $this->option('per-page');
        }

        $licenses = $manager->list($filters);

        if ($licenses->isEmpty()) {
            $this->info('No licenses found.');

            return self::SUCCESS;
        }

        $this->line('<options=bold>Licenses</>');
        $this->line(str_repeat('─', 80));
        $this->newLine();

        $tableData = [];

        foreach ($licenses as $license) {
            /** @var \DevRavik\LaravelLicensing\Models\License $license */
            $status = $this->formatStatus($license);
            $expiresAt = $license->expires_at?->format('Y-m-d H:i:s') ?? 'Never';
            $owner = $this->formatOwner($license);

            $tableData[] = [
                $license->id,
                $license->product,
                $owner,
                $license->seats,
                $expiresAt,
                $status,
                LicenseKeyHelper::mask($license->key ?? 'N/A'),
            ];
        }

        $this->table(
            ['ID', 'Product', 'Owner', 'Seats', 'Expires', 'Status', 'Key'],
            $tableData
        );

        if ($licenses instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            $this->newLine();
            $this->line("Showing {$licenses->firstItem()} to {$licenses->lastItem()} of {$licenses->total()} licenses");
        } else {
            $this->newLine();
            $this->line("Total: {$licenses->count()} license(s)");
        }

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
                return '<fg=yellow>Expired (Grace)</>';
            }

            return '<fg=yellow>Expired</>';
        }

        return '<fg=green>Active</>';
    }

    /**
     * Format owner information.
     */
    protected function formatOwner(LicenseContract $license): string
    {
        /** @var \DevRavik\LaravelLicensing\Models\License $license */
        $owner = $license->owner;

        if ($owner === null) {
            return "{$license->owner_type}:{$license->owner_id}";
        }

        $ownerName = 'ID:'.$owner->id;
        if (is_object($owner)) {
            if (method_exists($owner, 'name') && property_exists($owner, 'name')) {
                $ownerName = $owner->name;
            } elseif (method_exists($owner, 'email') && property_exists($owner, 'email')) {
                $ownerName = $owner->email;
            } elseif (property_exists($owner, 'id')) {
                $ownerName = 'ID:'.$owner->id;
            }
        }

        return class_basename($license->owner_type).": {$ownerName}";
    }
}
