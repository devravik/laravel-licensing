<?php

namespace DevRavik\LaravelLicensing\Console;

use Illuminate\Console\Command;

class GenerateLicenseKeysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'licensing:keys 
                            {--force : Overwrite existing keys}
                            {--show : Display the generated keys}
                            {--write : Automatically append keys to .env file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Ed25519 public/private key pair for license signing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! function_exists('sodium_crypto_sign_keypair')) {
            $this->error('libsodium extension is not available. Ed25519 signing requires PHP with libsodium support.');

            return self::FAILURE;
        }

        // Check if keys already exist
        $existingPrivateKey = config('license.signature.private_key');
        $existingPublicKey = config('license.signature.public_key');

        if (! $this->option('force') && ($existingPrivateKey || $existingPublicKey)) {
            $this->error('Keys already exist. Use --force to overwrite.');

            return self::FAILURE;
        }

        // Generate Ed25519 key pair
        $keypair = sodium_crypto_sign_keypair();

        $privateKey = base64_encode(
            sodium_crypto_sign_secretkey($keypair)
        );

        $publicKey = base64_encode(
            sodium_crypto_sign_publickey($keypair)
        );

        $this->info('✓ Ed25519 key pair generated successfully.');
        $this->newLine();

        // Show keys if requested
        if ($this->option('show')) {
            $this->line('Private Key:');
            $this->line($privateKey);
            $this->newLine();
            $this->line('Public Key:');
            $this->line($publicKey);
            $this->newLine();
        }

        // Display instructions
        $this->line('Add these to your .env file:');
        $this->newLine();
        $this->line("LICENSE_PRIVATE_KEY={$privateKey}");
        $this->line("LICENSE_PUBLIC_KEY={$publicKey}");
        $this->newLine();

        // Security warning
        $this->warn('SECURITY WARNING:');
        $this->line('  • Private key should NEVER be committed to version control');
        $this->line('  • Private key should NEVER be shipped with distributed software');
        $this->line('  • Public key can be embedded in client applications');
        $this->line('  • Signing should happen server-side only');
        $this->newLine();

        // Auto-write to .env if requested
        if ($this->option('write')) {
            $envPath = base_path('.env');

            if (! file_exists($envPath)) {
                $this->error('.env file not found. Please create it first.');

                return self::FAILURE;
            }

            $envContent = file_get_contents($envPath);

            // Remove existing keys if they exist
            $envContent = preg_replace('/^LICENSE_PRIVATE_KEY=.*$/m', '', $envContent);
            $envContent = preg_replace('/^LICENSE_PUBLIC_KEY=.*$/m', '', $envContent);
            $envContent = rtrim($envContent)."\n";

            // Append new keys
            $envContent .= "LICENSE_PRIVATE_KEY={$privateKey}\n";
            $envContent .= "LICENSE_PUBLIC_KEY={$publicKey}\n";

            file_put_contents($envPath, $envContent);

            $this->info('✓ Keys have been written to .env file.');
            $this->newLine();
            $this->warn('Remember to add .env to .gitignore if not already done!');
            $this->newLine();
        }

        return self::SUCCESS;
    }
}
