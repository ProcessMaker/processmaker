<?php

namespace ProcessMaker\Managers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ScreenCompiledManager
{
    /**
     * The storage disk to use.
     *
     * @var string
     */
    protected $storageDisk = 'local';

    /**
     * The directory where compiled screens are stored.
     *
     * @var string
     */
    protected $storagePath = 'compiled_screens/';

    /**
     * Store compiled content for a given screen ID.
     *
     * @param  string  $screenKey
     * @param  mixed   $compiledContent
     * @return void
     */
    public function storeCompiledContent(string $screenKey, $compiledContent)
    {
        $filename = $this->getFilename($screenKey);
        $serializedContent = serialize($compiledContent);

        Storage::disk($this->storageDisk)->put(
            $this->storagePath . $filename,
            $serializedContent
        );
    }

    /**
     * Retrieve compiled content by screen ID.
     *
     * @param  string  $screenKey
     * @return mixed|null
     */
    public function getCompiledContent(string $screenKey)
    {
        $filename = $this->getFilename($screenKey);

        if (Storage::disk($this->storageDisk)->exists($this->storagePath . $filename)) {
            $serializedContent = Storage::disk($this->storageDisk)->get(
                $this->storagePath . $filename
            );

            return unserialize($serializedContent);
        }

        return null;
    }

    /**
     * Clear all compiled assets from storage.
     *
     * @return void
     */
    public function clearCompiledAssets()
    {
        Storage::disk($this->storageDisk)->deleteDirectory($this->storagePath);

        // Recreate the directory to ensure it exists after deletion
        Storage::disk($this->storageDisk)->makeDirectory($this->storagePath);
    }

    public function clearProcessScreensCache(string $processId)
    {
        $files = Storage::disk($this->storageDisk)->files($this->storagePath);

        foreach ($files as $file) {
            if (strpos($file, "pid_{$processId}_") === 0) {
                Storage::disk($this->storageDisk)->delete($file);
            }
        }
    }

    public function createKey(string $processId, string $processVersionId, string $language, string $screenId, string $screenVersionId): string
    {
        return "pid_{$processId}_{$processVersionId}_{$language}_sid_{$screenId}_{$screenVersionId}";
    }

    public function getLastScreenVersionId()
    {
        $row = DB::select('SELECT id FROM screen_versions ORDER BY id DESC LIMIT 1;');
        return $row[0]->id;
    }

    /**
     * Generate a filename based on the screen ID.
     *
     * @param  string  $screenKey
     * @return string
     */
    protected function getFilename(string $screenKey)
    {
        return 'screen_' . $screenKey . '.bin';
    }
}
