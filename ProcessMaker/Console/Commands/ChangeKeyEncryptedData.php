<?php

namespace ProcessMaker\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use ProcessMaker\Facades\EncryptedData;
use Illuminate\Support\Facades\Log;

class ChangeKeyEncryptedData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:change-key-encrypted-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change the key used for encrypted data. The new key will be generated secure and randomly.';

    const message = 'Are you sure you\'d like to change the key used for encrypted data? This change cannot be undone.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            if ($this->confirm(self::message, false)) {
                // Get configured driver for encrypted data
                $driver = config('app.encrypted_data.driver');

                // Change key and update records
                EncryptedData::driver($driver)->changeKey();

                $this->info('Key for encrypted data succesfully changed.');
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
        }
    }
}
