<?php

namespace ProcessMaker\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\EncryptedData;

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
                // Get key before change it
                $oldKey = EncryptedData::getEncryptedDataKey();

                // Generate new key
                $newKey = Encrypter::generateKey(EncryptedData::ENCRYPTION_METHOD);

                // Get all encrypted data
                $records = EncryptedData::select(['id', 'iv', 'data'])->get();

                // Change values in all records
                foreach ($records as $record) {
                    // Decrypt text
                    $oldIv = base64_decode($record->iv);
                    $plainText = EncryptedData::decryptText($record->data, $oldIv, $oldKey);

                    // Encrypt text with new key
                    $newIv = EncryptedData::generateIv();
                    $cipherText = EncryptedData::encryptText($plainText, $newIv, $newKey);

                    // Store new values
                    $record->iv = base64_encode($newIv);
                    $record->data = $cipherText;
                    $record->save();
                }

                // Remove previous key from environment file
                EncryptedData::removeKeyFromEnvironmentFile();

                // Write new key in environment file
                EncryptedData::addKeyInEnvironmentFile($newKey);

                $this->info('Key for encrypted data succesfully changed.');
            }
        } catch (Exception $e) {dd($e->getMessage());
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
        }
    }
}
