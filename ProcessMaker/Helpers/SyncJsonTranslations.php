<?php

namespace ProcessMaker\Helpers;

class SyncJsonTranslations extends SyncTranslationsBase
{
    /**
     * Process all JSON translation files
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
     * Process a single JSON translation file
     *
     * @param string $languageCode
     * @return array
     */
    protected function processLanguageFile(string $languageCode): array
    {
        $filename = $languageCode . '.json';
        $result = [
            'filename' => $filename,
            'action' => 'none',
            'new_keys' => 0,
            'total_keys' => 0,
            'backup_created' => false,
            'error' => null,
        ];

        try {
            // Get content from resources-core
            $resourcesCoreContent = $this->getResourcesCoreContent($filename);
            if (!$resourcesCoreContent) {
                $result['error'] = 'Source file not found in resources-core';

                return $result;
            }

            // Decode resources-core content
            $resourcesCoreTranslations = json_decode($resourcesCoreContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $result['error'] = 'Invalid JSON in resources-core file: ' . json_last_error_msg();

                return $result;
            }

            // Check if destination file exists
            if (!$this->destinationFileExists($filename)) {
                // Copy the entire file from resources-core
                if ($this->copyFileFromResourcesCore($filename)) {
                    $result['action'] = 'copied';
                    $result['total_keys'] = count($resourcesCoreTranslations);
                } else {
                    $result['error'] = 'Failed to copy file from resources-core';
                }

                return $result;
            }

            // Get existing destination content
            $destinationContent = $this->getDestinationContent($filename);
            if (!$destinationContent) {
                $result['error'] = 'Failed to read destination file';

                return $result;
            }

            // Decode destination content
            $destinationTranslations = json_decode($destinationContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $result['error'] = 'Invalid JSON in destination file: ' . json_last_error_msg();

                return $result;
            }

            // Merge translations (only add new keys)
            $newKeysCount = 0;
            $mergedTranslations = $destinationTranslations;

            foreach ($resourcesCoreTranslations as $key => $value) {
                if (!array_key_exists($key, $mergedTranslations)) {
                    $mergedTranslations[$key] = $value;
                    $newKeysCount++;
                }
            }

            // Only update if there are new keys
            if ($newKeysCount > 0) {
                // Create backup before modifying the file
                $backupCreated = $this->createBackup($filename);
                $result['backup_created'] = $backupCreated;

                $mergedContent = json_encode($mergedTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                if ($this->saveToDestination($filename, $mergedContent)) {
                    $result['action'] = 'merged';
                    $result['new_keys'] = $newKeysCount;
                    $result['total_keys'] = count($mergedTranslations);

                    // Clean up old backups after successful save
                    $this->cleanupOldBackups($filename);
                } else {
                    $result['error'] = 'Failed to save merged translations';
                }
            } else {
                $result['action'] = 'no_changes';
                $result['total_keys'] = count($mergedTranslations);
            }

            // If processing en.json, sync missing keys to other language files
            if ($languageCode === 'en' && $result['action'] !== 'none' && $result['error'] === null) {
                $this->syncMissingKeysToOtherLanguages($mergedTranslations);
            }
        } catch (\Exception $e) {
            $result['error'] = 'Exception occurred: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Get all language files from the lang disk
     *
     * @return array
     */
    protected function getLanguageFilesFromLangDisk(): array
    {
        $languageFiles = [];
        $files = $this->langDisk->files();

        foreach ($files as $file) {
            if (preg_match('/^([a-z]{2})\.json$/', $file, $matches)) {
                $languageFiles[] = $matches[1];
            }
        }

        return $languageFiles;
    }

    /**
     * Sync missing keys from en.json to other language files
     *
     * @param array $enTranslations
     * @return void
     */
    protected function syncMissingKeysToOtherLanguages(array $enTranslations): void
    {
        $languageFiles = $this->getLanguageFilesFromLangDisk();

        foreach ($languageFiles as $languageCode) {
            // Skip en.json as it's the source
            if ($languageCode === 'en') {
                continue;
            }

            $filename = $languageCode . '.json';

            // Get existing content
            $existingContent = $this->getDestinationContent($filename);
            if (!$existingContent) {
                continue; // Skip if file doesn't exist
            }

            // Decode existing content
            $existingTranslations = json_decode($existingContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                continue; // Skip if invalid JSON
            }

            // Check for missing keys and add them with empty strings
            $hasNewKeys = false;
            $updatedTranslations = $existingTranslations;

            foreach ($enTranslations as $key => $value) {
                if (!array_key_exists($key, $updatedTranslations)) {
                    $updatedTranslations[$key] = '';
                    $hasNewKeys = true;
                }
            }

            // Save updated translations if there are new keys
            if ($hasNewKeys) {
                // Create backup before modifying the file
                $this->createBackup($filename);

                $updatedContent = json_encode($updatedTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                if ($this->saveToDestination($filename, $updatedContent)) {
                    // Clean up old backups after successful save
                    $this->cleanupOldBackups($filename);
                }
            }
        }
    }
}
