<?php

namespace ProcessMaker\Console\Commands;

use Exception;
use RuntimeException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncRecommendationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:sync-recommendations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs recommendations from GitHub';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->line('Syncing recommendations from GitHub...');

        try {
            /**
             * WIP Need to continue business logic here to fetch the
             * contents of each URL as JSON, parse it, check if we
             * have existing recommendations (matching UUID) and
             * if so, update them, or create new ones
             */
            $urls = static::remoteRecommendationsList();
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Returns an array of strings, each URL links to a JSON recommendation file.
     *
     * @return array
     */
    protected static function remoteRecommendationsList(): array
    {
        $response = Http::get(self::url('index.json'));

        if ($response->failed()) {
            throw new RuntimeException('Failed to retrieve recommendations from GitHub');
        }

        // JSON response is decoded into an array, where each key
        // represents a top-level directory containing JSON files,
        // each representing a recommendation
        $directories = json_decode(file_get_contents($response->json()), true);

        $urls = [];

        foreach ($directories as $dir => $files) {
            foreach ($files as $filename) {
                $urls[] = self::url($dir.'/'.$filename);
            }
        }

        return $urls;
    }

    /**
     * Build the direct URL to the file/directory in the repo
     *
     * @param  string  $filename
     *
     * @return string
     */
    protected static function url(string $filename): string
    {
        $base = config('services.recommendations_github.base_url');

        $repo = config('services.recommendations_github.repo');

        $branch = config('services.recommendations_github.branch');

        return "$base/$repo/$branch/$filename";
    }
}
