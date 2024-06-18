<?php

namespace ProcessMaker;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use ProcessMaker\Models\Recommendation;
use RuntimeException;

class SyncRecommendations
{
    /**
     * Filename in the repo containing the directory and file list
     *
     * @var string
     */
    protected static string $indexFileName = 'index.json';

    /**
     * Sync the Recommendations from the central repository
     *
     * @return void
     */
    public function sync(): void
    {
        // Build the list of http urls to fetch each json
        // file, then try to save each as model data for a
        // new or updated Recommendation
        $this->fetchRecommendations()->each(fn ($url) => $this->save($url));
    }

    /**
     * Indicates if a provided domain matches for this instance
     *
     * @param  string  $domain
     *
     * @return bool
     */
    protected static function matchesInstance(string $domain): bool
    {
        return Str::of(config('app.url'))
                  ->lower()
                  ->remove(['http:', 'https:', '/'])
                  ->is($domain);
    }

    /**
     * Retrieves the recommendation data from the provided
     * URL and attempts to save/update it locally
     *
     * @param  string  $url
     *
     * @return void
     */
    protected function save(string $url): void
    {
        // Retrieve the Recommendation model data from the repo
        $model_data = Http::get($url)->json();

        $recommendation = new Recommendation;

        // Check if one already exists on this instance
        $existing_recommendation = $recommendation->where('uuid', $model_data['uuid']);

        // If it does, we'll use that one, otherwise we create a new one
        if ($existing_recommendation->exists()) {
            $recommendation = $existing_recommendation->first();
        }

        // Then we persist it
        $recommendation->forceFill($model_data)->save();
    }

    /**
     * Returns an array of strings, each URL links to a JSON recommendation file.
     *
     * @return array
     */
    protected function fetchRecommendations(): Collection
    {
        // Retrieve the index of directories containing
        // the recommendation JSON files
        $branch = config('services.recommendations_github.branch');
        $url = 'https://api.github.com/repos/processmaker/pm4-recommendations/contents?ref=' . $branch;
        $response = Http::withHeaders($this->headers())
            ->get($url);

        if ($response->failed()) {
            throw new RuntimeException("Failed to retrieve recommendations from GitHub: {$response->reason()}");
        }

        // JSON response is decoded into an array, where each key
        // represents a top-level directory containing JSON files,
        // each representing a recommendation
        $files = $response->json();
        $urls = [];

        // Build the list of retrievable recommendation
        // JSON files by their URL
        foreach ($files as $file) {
            // We only want recommendation files that are either
            // default (for everyone) or located in a directory
            // named after this instance's domain.
            $name = $file['name'];
            $type = $file['type'];
            if ($type !== 'dir' || $name !== 'default' && !static::matchesInstance($name)) {
                continue;
            }

            $url = $file['url'];

            $directoryContents = Http::withHeaders($this->headers())
                ->get($url)
                ->json();

            // Build the complete http url to each JSON
            // file in the directory
            foreach ($directoryContents as $file) {
                $urls[] = $file['download_url'];
            }
        }

        return collect($urls);
    }

    private function headers(): array
    {
        return [
            'Accept' => 'application/vnd.github.v3+json',
            'Authorization' => 'Bearer ' . config('services.recommendations_github.token'),
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
    }
}
