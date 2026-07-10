<?php

declare(strict_types=1);

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Services\LoginCredentialEncryption;

class GenerateLoginKeys extends Command
{
    protected $signature = 'login:generate-keys {--force : Overwrite existing key files}';

    protected $description = 'Generate RSA key pair for encrypted login credentials';

    public function handle(LoginCredentialEncryption $encryption): int
    {
        if ($encryption->hasKeyPair() && !$this->option('force')) {
            $this->error('Login encryption keys already exist. Use --force to overwrite them.');

            return self::FAILURE;
        }

        $encryption->generateKeyPair();
        $this->info('Login encryption keys generated successfully.');

        return self::SUCCESS;
    }
}
