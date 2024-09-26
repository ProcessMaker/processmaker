<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\App;
use ProcessMaker\Facades\EncryptedData as EncryptedDataManager;
use ProcessMaker\Models\EncryptedData;
use Tests\TestCase;

class ChangeKeyEncryptedDataTest extends TestCase
{
    public function test_change_key_driver_local()
    {
        // Set driver to "local"
        $driver = 'local';
        config(['app.encrypted_data.driver' => $driver]);

        // Get current key
        $oldKey = EncryptedDataManager::driver($driver)->getEncryptedDataKey();

        // Create a record in encrypted fields table
        $encryptedDataOriginal = EncryptedData::factory()->create();

        // Get current IV and plain text
        $originalIv = base64_decode($encryptedDataOriginal->iv);
        $originalPlainText = EncryptedDataManager::driver($driver)->decryptText($encryptedDataOriginal->data, $originalIv);

        // Change the .env path to avoid override
        $originalPath = App::environmentPath();
        App::loadEnvironmentFrom('dummy-env');
        App::useEnvironmentPath(App::basePath() . '/tests/Fixtures');

        // Asserts for command outputs
        $this->artisan('processmaker:change-key-encrypted-data')
            ->expectsConfirmation('Are you sure you\'d like to change the key used for encrypted data? This change cannot be undone.', 'yes')
            ->expectsOutput('Key for encrypted data succesfully changed.')
            ->assertExitCode(0);

        // Get new key
        $newKey = EncryptedDataManager::driver($driver)->getEncryptedDataKey();

        // Assert that the key was changed
        $this->assertTrue($oldKey !== $newKey);

        // The original cipher text can't be decrypted with the new key
        $plainText = EncryptedDataManager::driver($driver)->decryptText($encryptedDataOriginal->data, $originalIv);
        $this->assertEmpty($plainText);

        // Get record previously created but with updated data
        $encryptedDataChanged = EncryptedData::find($encryptedDataOriginal->id);

        // Get IV and plain text from the updated record
        $updatedIv = base64_decode($encryptedDataChanged->iv);
        $updatedPlainText = EncryptedDataManager::driver($driver)->decryptText($encryptedDataChanged->data, $updatedIv);

        // The updated cipher text can't be decrypted with the old key
        $plainText = EncryptedDataManager::driver($driver)->decryptText($encryptedDataChanged->data, $updatedIv, $oldKey);
        $this->assertEmpty($plainText);

        // The original and updated plain texts should be the same
        $this->assertEquals($originalPlainText, $updatedPlainText);

        // Replenish .env path
        App::loadEnvironmentFrom('.env');
        App::useEnvironmentPath($originalPath);
    }
}
