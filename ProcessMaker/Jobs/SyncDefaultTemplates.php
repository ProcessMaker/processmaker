<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessTemplates;
use Throwable;

class SyncDefaultTemplates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const REPO_SHA_FILE = 'templates-sha.txt';

    public const CONTENT_HASHES_FILE = 'templates-content-hashes.json';

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Fetch default templates from a baked local checkout when present, otherwise GitHub.
     * Skip the whole sync when the configured branch SHA is unchanged, and skip individual
     * templates whose index hash matches the last successful sync.
     */
    public function handle()
    {
        try {
            $config = config('services.github');
            if (!$config) {
                return;
            }

            $categories = (strpos($config['template_categories'], ',') !== false)
                ? explode(',', $config['template_categories'])
                : [$config['template_categories']];

            $remoteSha = $this->fetchRemoteSha($config);
            $localSha = $this->readLocalSha($config);
            $currentSha = $remoteSha ?? $localSha;
            $storedSha = $this->readStoredSha();

            if ($currentSha && $storedSha && hash_equals($storedSha, $currentSha)) {
                Log::info("[SyncDefaultTemplates] Repo SHA unchanged ({$currentSha}), skipping sync");

                return;
            }

            // Prefer the baked tree when it matches remote (or remote is unreachable).
            $preferLocal = $this->localIndexPath($config) !== null
                && ($remoteSha === null || ($localSha !== null && hash_equals($localSha, $remoteSha)));

            $data = $this->loadIndex($config, $preferLocal);
            if ($data === null) {
                return;
            }

            $processCategoryId = ProcessCategory::firstOrCreate(
                ['name' => 'Default Templates'],
                [
                    'name' => 'Default Templates',
                    'status' => 'ACTIVE',
                    'is_system' => 0,
                ]
            )->getKey();

            $storedHashes = $this->readStoredHashes();
            $newHashes = $storedHashes;
            $hadFailure = false;

            foreach ($data as $templateCategory => $templates) {
                if (!in_array($templateCategory, $categories) && !in_array('all', $categories)) {
                    continue;
                }
                foreach ($templates as $template) {
                    $uuid = $template['uuid'] ?? null;
                    if (!$uuid) {
                        continue;
                    }

                    $existingTemplate = ProcessTemplates::where('uuid', $uuid)->first();
                    // Do not overwrite templates owned by a user.
                    if (!is_null($existingTemplate) && !is_null($existingTemplate->user_id)) {
                        continue;
                    }

                    $hash = $template['hash'] ?? null;
                    if ($hash && isset($storedHashes[$uuid]) && hash_equals((string) $storedHashes[$uuid], (string) $hash)) {
                        continue;
                    }

                    $payload = $this->loadTemplatePayload($config, $template, $preferLocal);
                    if ($payload === null) {
                        $hadFailure = true;
                        continue;
                    }

                    try {
                        data_set($payload, 'export.' . $payload['root'] . '.attributes.process_category_id', $processCategoryId);
                        $options = new Options([
                            'mode' => 'update',
                            'isTemplate' => true,
                            'saveAssetsMode' => 'saveAllAssets',
                        ]);
                        $importer = new Importer($payload, $options);
                        $importer->doImport();
                    } catch (Throwable $e) {
                        $hadFailure = true;
                        Log::warning("[SyncDefaultTemplates] Failed to import template {$template['name']} ({$uuid}): {$e->getMessage()}");
                        continue;
                    }

                    if ($hash) {
                        $newHashes[$uuid] = $hash;
                    }
                }
            }

            $this->writeStoredHashes($newHashes);
            // Only stamp the repo SHA when every needed template imported cleanly,
            // so a partial/network failure can retry on the next run.
            if ($currentSha && !$hadFailure) {
                $this->writeStoredSha($currentSha);
            }
        } catch (Throwable $e) {
            Log::warning("[SyncDefaultTemplates] Sync failed gracefully: {$e->getMessage()}");
        }
    }

    private function localPath(array $config): ?string
    {
        $path = $config['template_local_path'] ?? null;
        if (!$path || !is_dir($path)) {
            return null;
        }

        return rtrim($path, '/');
    }

    private function localIndexPath(array $config): ?string
    {
        $base = $this->localPath($config);
        if (!$base) {
            return null;
        }
        $index = $base . '/index.json';

        return is_file($index) ? $index : null;
    }

    private function readLocalSha(array $config): ?string
    {
        $base = $this->localPath($config);
        if (!$base) {
            return null;
        }
        $shaFile = $base . '/.templates-sha';
        if (!is_file($shaFile)) {
            return null;
        }
        $sha = trim((string) file_get_contents($shaFile));

        return $sha !== '' ? $sha : null;
    }

    private function fetchRemoteSha(array $config): ?string
    {
        $apiUrl = rtrim($config['api_url'] ?? 'https://api.github.com/repos/processmaker/', '/') . '/';
        $repo = $config['template_repo'] ?? 'process-templates';
        $branch = $config['template_branch'] ?? '2023-fall';
        $url = $apiUrl . $repo . '/git/ref/heads/' . $branch;

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Accept' => 'application/vnd.github+json'])
                ->get($url);
            if (!$response->successful()) {
                Log::warning("[SyncDefaultTemplates] Failed to fetch repo SHA from GitHub: {$url} - Status: {$response->status()}");

                return null;
            }
            $sha = $response->json('object.sha');

            return is_string($sha) && $sha !== '' ? $sha : null;
        } catch (ConnectionException $e) {
            Log::warning("[SyncDefaultTemplates] Timed out fetching repo SHA from GitHub: {$url} - {$e->getMessage()}");

            return null;
        } catch (Throwable $e) {
            Log::warning("[SyncDefaultTemplates] Error fetching repo SHA from GitHub: {$url} - {$e->getMessage()}");

            return null;
        }
    }

    private function loadIndex(array $config, bool $preferLocal): ?array
    {
        if ($preferLocal) {
            $indexPath = $this->localIndexPath($config);
            if ($indexPath) {
                $data = json_decode(file_get_contents($indexPath), true);
                if (is_array($data)) {
                    return $data;
                }
                Log::warning("[SyncDefaultTemplates] Failed to parse local template index: {$indexPath}");
            }
        }

        $url = $config['base_url'] . $config['template_repo'] . '/' . $config['template_branch'] . '/index.json';
        try {
            $response = Http::timeout(10)->get($url);
            if (!$response->successful()) {
                Log::warning("[SyncDefaultTemplates] Failed to fetch template index from GitHub: {$url} - Status: {$response->status()}");

                // Last resort: local bake even if we preferred remote.
                return $this->loadLocalIndexOnly($config);
            }

            return $response->json();
        } catch (ConnectionException $e) {
            Log::warning("[SyncDefaultTemplates] Timed out fetching template index from GitHub: {$url} - {$e->getMessage()}");

            return $this->loadLocalIndexOnly($config);
        }
    }

    private function loadLocalIndexOnly(array $config): ?array
    {
        $indexPath = $this->localIndexPath($config);
        if (!$indexPath) {
            return null;
        }
        $data = json_decode(file_get_contents($indexPath), true);
        if (!is_array($data)) {
            Log::warning("[SyncDefaultTemplates] Failed to parse local template index: {$indexPath}");

            return null;
        }

        return $data;
    }

    private function loadTemplatePayload(array $config, array $template, bool $preferLocal): ?array
    {
        $relativePath = ltrim($template['relative_path'] ?? '', './');
        if ($relativePath === '') {
            return null;
        }

        if ($preferLocal) {
            $base = $this->localPath($config);
            $localFile = $base ? $base . '/' . $relativePath : null;
            if ($localFile && is_file($localFile)) {
                $payload = json_decode(file_get_contents($localFile), true);
                if (is_array($payload)) {
                    return $payload;
                }
                Log::warning("[SyncDefaultTemplates] Failed to parse local template: {$localFile}");
            }
        }

        $url = $config['base_url'] . $config['template_repo'] . '/' . $config['template_branch'] . '/' . $relativePath;
        try {
            $response = Http::timeout(10)->get($url);
            if (!$response->successful()) {
                Log::warning("[SyncDefaultTemplates] Skipped template due to failed fetch: {$template['name']} ({$template['uuid']}) - Status: {$response->status()}");

                return $this->loadLocalTemplateOnly($config, $relativePath, $template);
            }

            return $response->json();
        } catch (ConnectionException $e) {
            Log::warning("[SyncDefaultTemplates] Skipped template due to failed fetch: {$template['name']} ({$template['uuid']}) - {$e->getMessage()}");

            return $this->loadLocalTemplateOnly($config, $relativePath, $template);
        }
    }

    private function loadLocalTemplateOnly(array $config, string $relativePath, array $template): ?array
    {
        $base = $this->localPath($config);
        $localFile = $base ? $base . '/' . $relativePath : null;
        if (!$localFile || !is_file($localFile)) {
            return null;
        }
        $payload = json_decode(file_get_contents($localFile), true);
        if (!is_array($payload)) {
            Log::warning("[SyncDefaultTemplates] Failed to parse local template: {$localFile}");

            return null;
        }

        return $payload;
    }

    private function readStoredSha(): ?string
    {
        if (!Storage::disk('local')->exists(self::REPO_SHA_FILE)) {
            return null;
        }
        $sha = trim((string) Storage::disk('local')->get(self::REPO_SHA_FILE));

        return $sha !== '' ? $sha : null;
    }

    private function writeStoredSha(string $sha): void
    {
        Storage::disk('local')->put(self::REPO_SHA_FILE, $sha . "\n");
    }

    private function readStoredHashes(): array
    {
        if (!Storage::disk('local')->exists(self::CONTENT_HASHES_FILE)) {
            return [];
        }
        $decoded = json_decode((string) Storage::disk('local')->get(self::CONTENT_HASHES_FILE), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function writeStoredHashes(array $hashes): void
    {
        Storage::disk('local')->put(
            self::CONTENT_HASHES_FILE,
            json_encode($hashes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );
    }
}
