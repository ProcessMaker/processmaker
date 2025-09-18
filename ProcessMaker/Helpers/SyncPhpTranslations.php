<?php

namespace ProcessMaker\Helpers;

class SyncPhpTranslations extends SyncTranslationsBase
{
    /**
     * Process all PHP translation files
     *
     * @return array
     */
    public function sync(): array
    {
        $results = [];
        $languageCodes = $this->getLanguageCodes();

        foreach ($languageCodes as $languageCode) {
            $results[$languageCode] = $this->processLanguageFile($languageCode);
        }

        return $results;
    }

    /**
     * Process a single language's PHP translation files
     *
     * @param string $languageCode
     * @return array
     */
    protected function processLanguageFile(string $languageCode): array
    {
        $result = [
            'language' => $languageCode,
            'files_processed' => 0,
            'files_copied' => 0,
            'files_merged' => 0,
            'files_no_changes' => 0,
            'errors' => [],
            'details' => [],
        ];

        $sourceDir = $this->resourcesCorePath . '/' . $languageCode;
        $destinationDir = $languageCode;

        // Check if source directory exists
        if (!is_dir($sourceDir)) {
            $result['errors'][] = "Source directory not found: {$sourceDir}";

            return $result;
        }

        // Get all PHP files from source directory
        $phpFiles = glob($sourceDir . '/*.php');

        foreach ($phpFiles as $sourceFile) {
            $filename = basename($sourceFile);
            $fileResult = $this->processPhpFile($languageCode, $filename);

            $result['files_processed']++;
            $result['details'][$filename] = $fileResult;

            switch ($fileResult['action']) {
                case 'copied':
                    $result['files_copied']++;
                    break;
                case 'merged':
                    $result['files_merged']++;
                    break;
                case 'no_changes':
                    $result['files_no_changes']++;
                    break;
                case 'error':
                    $result['errors'][] = "{$filename}: {$fileResult['error']}";
                    break;
            }
        }

        return $result;
    }

    /**
     * Process a single PHP translation file
     *
     * @param string $languageCode
     * @param string $filename
     * @return array
     */
    private function processPhpFile(string $languageCode, string $filename): array
    {
        $fileResult = [
            'filename' => $filename,
            'action' => 'none',
            'new_keys' => 0,
            'total_keys' => 0,
            'backup_created' => false,
            'error' => null,
        ];

        try {
            // Get source content
            $sourcePath = $this->resourcesCorePath . '/' . $languageCode . '/' . $filename;
            $sourceContent = file_get_contents($sourcePath);

            if ($sourceContent === false) {
                $fileResult['error'] = 'Failed to read source file';
                $fileResult['action'] = 'error';

                return $fileResult;
            }

            // Parse source PHP array
            $sourceTranslations = $this->parsePhpArray($sourceContent);
            if ($sourceTranslations === null) {
                $fileResult['error'] = 'Invalid PHP array in source file';
                $fileResult['action'] = 'error';

                return $fileResult;
            }

            // Check if destination file exists
            $destinationPath = $languageCode . '/' . $filename;
            if (!$this->destinationFileExists($destinationPath)) {
                // Copy the entire file from resources-core
                if ($this->copyPhpFileFromResourcesCore($languageCode, $filename)) {
                    $fileResult['action'] = 'copied';
                    $fileResult['total_keys'] = count($sourceTranslations);
                } else {
                    $fileResult['error'] = 'Failed to copy file from resources-core';
                    $fileResult['action'] = 'error';
                }

                return $fileResult;
            }

            // Get existing destination content
            $destinationContent = $this->getDestinationContent($destinationPath);
            if (!$destinationContent) {
                $fileResult['error'] = 'Failed to read destination file';
                $fileResult['action'] = 'error';

                return $fileResult;
            }

            // Parse destination PHP array
            $destinationTranslations = $this->parsePhpArray($destinationContent);
            if ($destinationTranslations === null) {
                $fileResult['error'] = 'Invalid PHP array in destination file';
                $fileResult['action'] = 'error';

                return $fileResult;
            }

            // Merge translations (only add new keys)
            $newKeysCount = 0;
            $mergedTranslations = $destinationTranslations;

            foreach ($sourceTranslations as $key => $value) {
                if (!array_key_exists($key, $mergedTranslations)) {
                    $mergedTranslations[$key] = $value;
                    $newKeysCount++;
                }
            }

            // Only update if there are new keys
            if ($newKeysCount > 0) {
                // Create backup before modifying the file
                $backupCreated = $this->createBackup($destinationPath);
                $fileResult['backup_created'] = $backupCreated;

                $mergedContent = $this->generatePhpArray($mergedTranslations, $sourceContent);
                if ($this->saveToDestination($destinationPath, $mergedContent)) {
                    $fileResult['action'] = 'merged';
                    $fileResult['new_keys'] = $newKeysCount;
                    $fileResult['total_keys'] = count($mergedTranslations);

                    // Clean up old backups after successful save
                    $this->cleanupOldBackups($destinationPath);
                } else {
                    $fileResult['error'] = 'Failed to save merged translations';
                    $fileResult['action'] = 'error';
                }
            } else {
                $fileResult['action'] = 'no_changes';
                $fileResult['total_keys'] = count($mergedTranslations);
            }
        } catch (\Exception $e) {
            $fileResult['error'] = 'Exception occurred: ' . $e->getMessage();
            $fileResult['action'] = 'error';
        }

        return $fileResult;
    }

    /**
     * Parse PHP array from file content
     *
     * @param string $content
     * @return array|null
     */
    private function parsePhpArray(string $content): ?array
    {
        // Create a temporary file to use PHP's include functionality safely
        $tempFile = tempnam(sys_get_temp_dir(), 'php_trans_');
        file_put_contents($tempFile, $content);

        try {
            $translations = include $tempFile;
            unlink($tempFile);

            if (is_array($translations)) {
                return $translations;
            }
        } catch (\Throwable $e) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }

        return null;
    }

    /**
     * Generate PHP array content
     *
     * @param array $translations
     * @param string $originalContent
     * @return string
     */
    private function generatePhpArray(array $translations, string $originalContent): string
    {
        // Extract the header comment from original content
        $headerComment = '';
        if (preg_match('/^<\?php\s*(?:\/\*.*?\*\/\s*)?return\s*\[/s', $originalContent, $matches)) {
            $headerComment = $matches[0];
        } else {
            $headerComment = "<?php\n\nreturn [\n";
        }

        $content = $headerComment;

        foreach ($translations as $key => $value) {
            $escapedKey = $this->escapePhpString($key);
            $escapedValue = $this->formatPhpValue($value, 1);
            $content .= "    {$escapedKey} => {$escapedValue},\n";
        }

        $content .= "\n];\n";

        return $content;
    }

    /**
     * Format PHP value for output (handles strings and arrays)
     *
     * @param mixed $value
     * @param int $indentLevel
     * @return string
     */
    private function formatPhpValue($value, int $indentLevel = 0): string
    {
        if (is_array($value)) {
            return $this->formatPhpArray($value, $indentLevel);
        }

        return $this->escapePhpString($value);
    }

    /**
     * Format PHP array for output
     *
     * @param array $array
     * @param int $indentLevel
     * @return string
     */
    private function formatPhpArray(array $array, int $indentLevel = 0): string
    {
        $indent = str_repeat('    ', $indentLevel);
        $nextIndent = str_repeat('    ', $indentLevel + 1);

        $content = "[\n";

        foreach ($array as $key => $value) {
            $escapedKey = $this->escapePhpString($key);
            $formattedValue = $this->formatPhpValue($value, $indentLevel + 1);
            $content .= "{$nextIndent}{$escapedKey} => {$formattedValue},\n";
        }

        $content .= "{$indent}]";

        return $content;
    }

    /**
     * Escape PHP string for output
     *
     * @param string $string
     * @return string
     */
    private function escapePhpString(string $string): string
    {
        return "'" . str_replace("'", "\\'", $string) . "'";
    }

    /**
     * Copy PHP file from resources-core to destination
     *
     * @param string $languageCode
     * @param string $filename
     * @return bool
     */
    private function copyPhpFileFromResourcesCore(string $languageCode, string $filename): bool
    {
        $sourcePath = $this->resourcesCorePath . '/' . $languageCode . '/' . $filename;
        $destinationPath = $languageCode . '/' . $filename;

        if (!file_exists($sourcePath)) {
            return false;
        }

        $content = file_get_contents($sourcePath);

        return $this->saveToDestination($destinationPath, $content);
    }
}
