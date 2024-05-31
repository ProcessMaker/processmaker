<?php

namespace ProcessMaker;

use RuntimeException;
use Illuminate\Support\Facades\Http;

class SyncRecommendations
{
    /**
     * @throws \JsonException
     */
    public static function sync(): void
    {
        /**
         * TODO This is a work in progress
         */
        $urls = self::recommendationJsonUrls();
    }

    /**
     * @throws \JsonException
     */
    protected static function recommendationJsonUrls(): array
    {
         $response = Http::get(self::url('index.json'));

        if ($response->failed()) {
            throw new RuntimeException('Failed to retrieve recommendations from GitHub');
        }

        // JSON response is decoded into an array, where each key
        // represents a top-level directory containing JSON files,
        // each representing a recommendation
        $directories = json_decode(file_get_contents($response->json()), true, 512, JSON_THROW_ON_ERROR);

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
