<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Helpers\SyncPhpTranslations;
use Tests\TestCase;

class SyncPhpTranslationsTest extends TestCase
{
    protected $tempDir;

    protected $syncTranslations;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary directory for resources-core
        $this->tempDir = sys_get_temp_dir() . '/sync_php_translations_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->tempDir . '/lang', 0755, true);

        // Set the resources-core path to our temp directory
        Config::set('app.resources_core_path', $this->tempDir);

        // Create fake storage for lang disk
        Storage::fake('lang');

        $this->syncTranslations = new SyncPhpTranslations();
    }

    protected function tearDown(): void
    {
        // Clean up temporary directory
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    /**
     * Test copying new PHP translation files when destination doesn't exist
     */
    public function testCopyNewPhpTranslationFiles()
    {
        // Create test PHP files in resources-core
        $this->createTestPhpFile('en/auth.php', [
            'failed' => 'These credentials do not match our records.',
            'password' => 'The provided password is incorrect.',
            'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
        ]);

        $this->createTestPhpFile('en/validation.php', [
            'required' => 'The :attribute field is required.',
            'email' => 'The :attribute must be a valid email address.',
        ]);

        // Run sync
        $results = $this->syncTranslations->sync();

        // Debug: Print sync results
        echo 'Sync results: ';
        var_dump($results);

        // Assert results
        $this->assertArrayHasKey('en', $results);
        $this->assertEquals(2, $results['en']['files_processed']);
        $this->assertEquals(2, $results['en']['files_copied']);
        $this->assertEquals(0, $results['en']['files_merged']);
        $this->assertEmpty($results['en']['errors']);

        // Verify files were copied correctly
        $this->assertTrue(Storage::disk('lang')->exists('en/auth.php'));
        $this->assertTrue(Storage::disk('lang')->exists('en/validation.php'));

        // Verify content was copied correctly
        $authContent = Storage::disk('lang')->get('en/auth.php');
        $this->assertStringContainsString("'failed' => 'These credentials do not match our records.'", $authContent);

        $validationContent = Storage::disk('lang')->get('en/validation.php');
        $this->assertStringContainsString("'required' => 'The :attribute field is required.'", $validationContent);
    }

    /**
     * Test merging new translations into existing PHP files
     */
    public function testMergeNewPhpTranslations()
    {
        // Create existing translations in destination
        $existingAuth = [
            'failed' => 'Custom failed message.',
            'password' => 'The provided password is incorrect.',
        ];
        Storage::disk('lang')->put('en/auth.php', $this->generatePhpContent($existingAuth));

        // Create resources-core with additional translations
        $resourcesCoreAuth = [
            'failed' => 'These credentials do not match our records.',
            'password' => 'The provided password is incorrect.',
            'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
            'new_key' => 'New translation value.',
        ];
        $this->createTestPhpFile('en/auth.php', $resourcesCoreAuth);

        // Run sync
        $results = $this->syncTranslations->sync();

        // Assert results
        $this->assertArrayHasKey('en', $results);
        $this->assertEquals(1, $results['en']['files_processed']);
        $this->assertEquals(0, $results['en']['files_copied']);
        $this->assertEquals(1, $results['en']['files_merged']);
        $this->assertEmpty($results['en']['errors']);

        // Verify merged content
        $mergedContent = Storage::disk('lang')->get('en/auth.php');
        $this->assertStringContainsString("'failed' => 'Custom failed message.'", $mergedContent); // Preserved custom value
        $this->assertStringContainsString("'throttle' => 'Too many login attempts. Please try again in :seconds seconds.'", $mergedContent); // New from resources-core
        $this->assertStringContainsString("'new_key' => 'New translation value.'", $mergedContent); // New from resources-core
    }

    /**
     * Test no changes when all translations already exist
     */
    public function testNoChangesWhenAllPhpTranslationsExist()
    {
        // Create existing translations in destination
        $existingTranslations = [
            'failed' => 'These credentials do not match our records.',
            'password' => 'The provided password is incorrect.',
            'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
        ];
        Storage::disk('lang')->put('en/auth.php', $this->generatePhpContent($existingTranslations));

        // Create resources-core with same translations
        $this->createTestPhpFile('en/auth.php', $existingTranslations);

        // Run sync
        $results = $this->syncTranslations->sync();

        // Assert results
        $this->assertArrayHasKey('en', $results);
        $this->assertEquals(1, $results['en']['files_processed']);
        $this->assertEquals(0, $results['en']['files_copied']);
        $this->assertEquals(0, $results['en']['files_merged']);
        $this->assertEquals(1, $results['en']['files_no_changes']);
        $this->assertEmpty($results['en']['errors']);
    }

    /**
     * Test handling invalid PHP in resources-core
     */
    public function testInvalidPhpInResourcesCore()
    {
        // Create invalid PHP file in resources-core
        $invalidContent = "<?php\n\nreturn [\n    'failed' => 'These credentials do not match our records.',\n    'password' =>\n];\n";
        mkdir($this->tempDir . '/lang/en', 0755, true);
        file_put_contents($this->tempDir . '/lang/en/auth.php', $invalidContent);

        // Run sync
        $results = $this->syncTranslations->sync();

        // Assert results
        $this->assertArrayHasKey('en', $results);
        $this->assertEquals(1, $results['en']['files_processed']);
        $this->assertEquals(1, count($results['en']['errors']));
        $this->assertStringContainsString('Invalid PHP array', $results['en']['errors'][0]);
    }

    /**
     * Test handling invalid PHP in destination
     */
    public function testInvalidPhpInDestination()
    {
        // Create invalid PHP in destination
        $invalidContent = "<?php\n\nreturn [\n    'failed' => 'These credentials do not match our records.',\n    'password' =>\n];\n";
        Storage::disk('lang')->put('en/auth.php', $invalidContent);

        // Create valid PHP in resources-core
        $validTranslations = [
            'failed' => 'These credentials do not match our records.',
            'password' => 'The provided password is incorrect.',
            'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
        ];
        $this->createTestPhpFile('en/auth.php', $validTranslations);

        // Run sync
        $results = $this->syncTranslations->sync();

        // Assert results
        $this->assertArrayHasKey('en', $results);
        $this->assertEquals(1, $results['en']['files_processed']);
        $this->assertEquals(1, count($results['en']['errors']));
        $this->assertStringContainsString('Invalid PHP array', $results['en']['errors'][0]);
    }

    /**
     * Test processing multiple language files
     */
    public function testProcessMultiplePhpLanguages()
    {
        // Create multiple language files in resources-core
        $this->createTestPhpFile('en/auth.php', ['failed' => 'These credentials do not match our records.']);
        $this->createTestPhpFile('es/auth.php', ['failed' => 'Estas credenciales no coinciden con nuestros registros.']);
        $this->createTestPhpFile('fr/auth.php', ['failed' => 'Ces identifiants ne correspondent pas à nos enregistrements.']);

        // Run sync
        $results = $this->syncTranslations->sync();

        // Assert results
        $this->assertArrayHasKey('en', $results);
        $this->assertArrayHasKey('es', $results);
        $this->assertArrayHasKey('fr', $results);

        $this->assertEquals(1, $results['en']['files_copied']);
        $this->assertEquals(1, $results['es']['files_copied']);
        $this->assertEquals(1, $results['fr']['files_copied']);

        // Verify all files were created
        $this->assertTrue(Storage::disk('lang')->exists('en/auth.php'));
        $this->assertTrue(Storage::disk('lang')->exists('es/auth.php'));
        $this->assertTrue(Storage::disk('lang')->exists('fr/auth.php'));
    }

    /**
     * Test preserving existing custom translations
     */
    public function testPreserveExistingCustomPhpTranslations()
    {
        // Create existing translations with custom values
        $existingTranslations = [
            'failed' => 'Custom failed message.',
            'password' => 'Custom password message.',
            'custom_key' => 'Custom value',
        ];
        Storage::disk('lang')->put('en/auth.php', $this->generatePhpContent($existingTranslations));

        // Create resources-core with different values for existing keys and new keys
        $resourcesCoreTranslations = [
            'failed' => 'Default failed message.',
            'password' => 'Default password message.',
            'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
            'new_key' => 'New translation value.',
        ];
        $this->createTestPhpFile('en/auth.php', $resourcesCoreTranslations);

        // Run sync
        $results = $this->syncTranslations->sync();

        // Assert results
        $this->assertEquals(1, $results['en']['files_merged']);
        $this->assertEquals(2, $results['en']['details']['auth.php']['new_keys']); // throttle and new_key

        // Verify custom translations were preserved
        $mergedContent = Storage::disk('lang')->get('en/auth.php');
        $this->assertStringContainsString("'failed' => 'Custom failed message.'", $mergedContent); // Preserved custom value
        $this->assertStringContainsString("'custom_key' => 'Custom value'", $mergedContent); // Preserved custom key
        $this->assertStringContainsString("'throttle' => 'Too many login attempts. Please try again in :seconds seconds.'", $mergedContent); // New from resources-core
        $this->assertStringContainsString("'new_key' => 'New translation value.'", $mergedContent); // New from resources-core
    }

    /**
     * Test processing multiple PHP files in same language
     */
    public function testProcessMultiplePhpFilesInSameLanguage()
    {
        // Create multiple PHP files in resources-core
        $this->createTestPhpFile('en/auth.php', ['failed' => 'These credentials do not match our records.']);
        $this->createTestPhpFile('en/validation.php', ['required' => 'The :attribute field is required.']);
        $this->createTestPhpFile('en/passwords.php', ['reset' => 'Your password has been reset.']);

        // Run sync
        $results = $this->syncTranslations->sync();

        // Assert results
        $this->assertArrayHasKey('en', $results);
        $this->assertEquals(3, $results['en']['files_processed']);
        $this->assertEquals(3, $results['en']['files_copied']);

        // Verify all files were created
        $this->assertTrue(Storage::disk('lang')->exists('en/auth.php'));
        $this->assertTrue(Storage::disk('lang')->exists('en/validation.php'));
        $this->assertTrue(Storage::disk('lang')->exists('en/passwords.php'));
    }

    /**
     * Test backup creation when merging PHP translations
     */
    public function testBackupCreationWhenMergingPhp()
    {
        // Create existing translations in destination
        $existingTranslations = [
            'failed' => 'Custom failed message.',
            'password' => 'The provided password is incorrect.',
        ];

        // Try using a flat filename to avoid nested path issues
        Storage::disk('lang')->put('en/auth.php', $this->generatePhpContent($existingTranslations));

        // Debug: Check if the file was created
        echo 'File exists after creation: ' . (Storage::disk('lang')->exists('en/auth.php') ? 'yes' : 'no') . "\n";
        echo 'File content: ' . Storage::disk('lang')->get('en/auth.php') . "\n";

        // Debug: Check what files exist before sync
        echo 'Files before sync: ';
        var_dump(Storage::disk('lang')->files());

        // Create resources-core with additional translations
        $resourcesCoreTranslations = [
            'failed' => 'These credentials do not match our records.',
            'password' => 'The provided password is incorrect.',
            'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
        ];
        $this->createTestPhpFile('en/auth.php', $resourcesCoreTranslations);

        // Run sync
        $results = $this->syncTranslations->sync();

        // Debug: Check what files exist after sync
        echo 'Files after sync: ';
        var_dump(Storage::disk('lang')->files());

        // Assert results
        dump($results);
        $this->assertEquals('merged', $results['en']['details']['auth.php']['action']);
        $this->assertTrue($results['en']['details']['auth.php']['backup_created']);

        // Verify backup file was created
        $backupFiles = $this->getBackupFiles('en/auth.php');

        // Debug: Print all files in storage
        echo 'All files in storage: ';
        var_dump(Storage::disk('lang')->files());
        echo 'Backup files found: ';
        var_dump($backupFiles);

        $this->assertCount(1, $backupFiles);

        // Verify backup content matches original
        $backupContent = Storage::disk('lang')->get($backupFiles[0]);
        $this->assertStringContainsString("'failed' => 'Custom failed message.'", $backupContent);
        $this->assertStringContainsString("'password' => 'The provided password is incorrect.'", $backupContent);
    }

    /**
     * Test no backup creation when no changes are made to PHP files
     */
    public function testNoBackupCreationWhenNoPhpChanges()
    {
        // Create existing translations in destination
        $existingTranslations = [
            'failed' => 'These credentials do not match our records.',
            'password' => 'The provided password is incorrect.',
        ];
        Storage::disk('lang')->put('en/auth.php', $this->generatePhpContent($existingTranslations));

        // Create resources-core with same translations
        $this->createTestPhpFile('en/auth.php', $existingTranslations);

        // Run sync
        $results = $this->syncTranslations->sync();

        // Assert results
        $this->assertEquals('no_changes', $results['en']['details']['auth.php']['action']);
        $this->assertFalse($results['en']['details']['auth.php']['backup_created']);

        // Verify no backup files were created
        $backupFiles = $this->getBackupFiles('en/auth.php');
        $this->assertCount(0, $backupFiles);
    }

    /**
     * Test backup rotation for PHP files (keeping only 3 most recent backups)
     */
    public function testBackupRotationForPhpFiles()
    {
        // Create existing translations in destination
        $existingTranslations = [
            'failed' => 'Custom failed message.',
            'password' => 'The provided password is incorrect.',
        ];
        Storage::disk('lang')->put('en/auth.php', $this->generatePhpContent($existingTranslations));

        // Run sync multiple times to create multiple backups
        for ($i = 0; $i < 5; $i++) {
            // Create resources-core with additional translations (different each time)
            $resourcesCoreTranslations = [
                'failed' => 'These credentials do not match our records.',
                'password' => 'The provided password is incorrect.',
                'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
            ];

            // Add a new key each time to ensure changes are detected
            $resourcesCoreTranslations['new_key_' . $i] = 'Value ' . $i;

            $this->createTestPhpFile('en/auth.php', $resourcesCoreTranslations);

            $this->syncTranslations->sync();

            // Small delay to ensure different timestamps
            usleep(1000);
        }

        // Verify only 3 backup files exist
        $backupFiles = $this->getBackupFiles('en/auth.php');
        $this->assertCount(3, $backupFiles);

        // Verify the backup files have different timestamps
        $timestamps = [];
        foreach ($backupFiles as $backupFile) {
            if (preg_match('/\.bak\.(\d+)$/', $backupFile, $matches)) {
                $timestamps[] = (int) $matches[1];
            }
        }

        $this->assertCount(3, $timestamps);
        $this->assertEquals(count($timestamps), count(array_unique($timestamps)));
    }

    /**
     * Test no backup creation when copying new PHP files
     */
    public function testNoBackupCreationWhenCopyingPhp()
    {
        // Create test PHP files in resources-core
        $this->createTestPhpFile('en/auth.php', [
            'failed' => 'These credentials do not match our records.',
            'password' => 'The provided password is incorrect.',
        ]);

        // Run sync
        $results = $this->syncTranslations->sync();

        // Assert results
        $this->assertEquals('copied', $results['en']['details']['auth.php']['action']);
        $this->assertFalse($results['en']['details']['auth.php']['backup_created']);

        // Verify no backup files were created
        $backupFiles = $this->getBackupFiles('en/auth.php');
        $this->assertCount(0, $backupFiles);
    }

    /**
     * Helper method to get backup files for a given file
     */
    private function getBackupFiles(string $filepath): array
    {
        $backupFiles = [];
        $files = Storage::disk('lang')->files();

        foreach ($files as $file) {
            if (preg_match('/^' . preg_quote($filepath, '/') . '\.bak\.\d+(?:\.\d+)?$/', $file)) {
                $backupFiles[] = $file;
            }
        }

        return $backupFiles;
    }

    /**
     * Helper method to create test PHP files
     */
    private function createTestPhpFile(string $path, array $translations): void
    {
        $fullPath = $this->tempDir . '/lang/' . $path;
        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = $this->generatePhpContent($translations);
        file_put_contents($fullPath, $content);
    }

    /**
     * Helper method to generate PHP content
     */
    private function generatePhpContent(array $translations): string
    {
        $content = "<?php\n\nreturn [\n";

        foreach ($translations as $key => $value) {
            $content .= "    '{$key}' => '{$value}',\n";
        }

        $content .= "\n];\n";

        return $content;
    }

    /**
     * Helper method to remove directory recursively
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
