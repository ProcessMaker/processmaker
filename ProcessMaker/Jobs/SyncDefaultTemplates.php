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
     * Sync default templates from the configured branch.
     *
     * - Skip entirely when the GitHub branch SHA matches the last successful sync.
     * - Prefer the baked local tree when its SHA matches GitHub (or GitHub is unreachable),
     *   which is the new-instance / upgrade path.
     * - When GitHub has moved ahead of the bake, fetch index and bodies from GitHub only.
     *   Do not fall back to the stale bake and stamp remote success markers.
     */
    public function handle()
    {
        try {
            $config = config('services.github');
            if ($config) {
                $this->sync($config);
            }
        } catch (Throwable $e) {
            Log::warning("[SyncDefaultTemplates] Sync failed gracefully: {$e->getMessage()}");
        }
    }

    private function sync(array $config): void
    {
        $categories = $this->parseCategories($config);
        $remoteSha = $this->fetchRemoteSha($config);
        $localSha = $this->readLocalSha($config);
        $currentSha = $remoteSha ?? $localSha;

        if ($this->shaUnchanged($currentSha)) {
            return;
        }

        $preferLocal = $this->shouldPreferLocal($config, $remoteSha, $localSha);
        $data = $this->loadIndex($config, $preferLocal);
        if ($data === null) {
            return;
        }

        $processCategoryId = $this->defaultProcessCategoryId();
        $result = $this->importTemplates($data, $categories, $config, $preferLocal, $processCategoryId);

        $this->writeStoredHashes($result['hashes']);
        // Only stamp the repo SHA when every needed template imported cleanly,
        // so a partial/network failure can retry on the next run.
        if ($currentSha && !$result['hadFailure']) {
            $this->writeStoredSha($currentSha);
        }
    }

    private function parseCategories(array $config): array
    {
        $categories = $config['template_categories'];

        return strpos($categories, ',') !== false ? explode(',', $categories) : [$categories];
    }

    private function shaUnchanged(?string $currentSha): bool
    {
        $storedSha = $this->readStoredSha();
        if (!$currentSha || !$storedSha || !hash_equals($storedSha, $currentSha)) {
            return false;
        }

        Log::info("[SyncDefaultTemplates] Repo SHA unchanged ({$currentSha}), skipping sync");

        return true;
    }

    private function shouldPreferLocal(array $config, ?string $remoteSha, ?string $localSha): bool
    {
        if ($this->localIndexPath($config) === null) {
            return false;
        }

        return $remoteSha === null || ($localSha !== null && hash_equals($localSha, $remoteSha));
    }

    private function defaultProcessCategoryId(): string|int
    {
        return ProcessCategory::firstOrCreate(
            ['name' => 'Default Templates'],
            [
                'name' => 'Default Templates',
                'status' => 'ACTIVE',
                'is_system' => 0,
            ]
        )->getKey();
    }

    private function importTemplates(
        array $data,
        array $categories,
        array $config,
        bool $preferLocal,
        string|int $processCategoryId
    ): array {
        $storedHashes = $this->readStoredHashes();
        $newHashes = $storedHashes;
        $hadFailure = false;

        foreach ($data as $templateCategory => $templates) {
            if (!$this->categoryAllowed($templateCategory, $categories)) {
                continue;
            }
            foreach ($templates as $template) {
                $hash = $this->importOneTemplate($template, $config, $preferLocal, $processCategoryId, $storedHashes);
                if ($hash === false) {
                    $hadFailure = true;
                } elseif (is_string($hash)) {
                    $newHashes[$template['uuid']] = $hash;
                }
            }
        }

        return ['hashes' => $newHashes, 'hadFailure' => $hadFailure];
    }

    private function categoryAllowed(string $templateCategory, array $categories): bool
    {
        return in_array($templateCategory, $categories) || in_array('all', $categories);
    }

    /**
     * Import one template.
     *
     * @return string|false|null Hash when imported, false on failure, null when skipped.
     */
    private function importOneTemplate(
        array $template,
        array $config,
        bool $preferLocal,
        string|int $processCategoryId,
        array $storedHashes
    ): string|false|null {
        $uuid = $template['uuid'] ?? null;
        if (!$uuid || $this->shouldSkipTemplate($uuid, $template['hash'] ?? null, $storedHashes)) {
            return null;
        }

        $payload = $this->loadTemplatePayload($config, $template, $preferLocal);
        if ($payload === null || !$this->runImport($payload, $processCategoryId, $template, $uuid)) {
            return false;
        }

        $hash = $template['hash'] ?? null;

        return $hash ? (string) $hash : null;
    }

    private function shouldSkipTemplate(string $uuid, ?string $hash, array $storedHashes): bool
    {
        $existingTemplate = ProcessTemplates::where('uuid', $uuid)->first();
        // Do not overwrite templates owned by a user.
        if (!is_null($existingTemplate) && !is_null($existingTemplate->user_id)) {
            return true;
        }

        return $hash
            && isset($storedHashes[$uuid])
            && hash_equals((string) $storedHashes[$uuid], (string) $hash);
    }

    private function runImport(array $payload, string|int $processCategoryId, array $template, string $uuid): bool
    {
        try {
            data_set($payload, 'export.' . $payload['root'] . '.attributes.process_category_id', $processCategoryId);
            $options = new Options([
                'mode' => 'update',
                'isTemplate' => true,
                'saveAssetsMode' => 'saveAllAssets',
            ]);
            $importer = new Importer($payload, $options);
            $importer->doImport();

            return true;
        } catch (Throwable $e) {
            Log::warning("[SyncDefaultTemplates] Failed to import template {$template['name']} ({$uuid}): {$e->getMessage()}");

            return false;
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
        } catch (Throwable $e) {
            $prefix = $e instanceof ConnectionException ? 'Timed out' : 'Error';
            Log::warning("[SyncDefaultTemplates] {$prefix} fetching repo SHA from GitHub: {$url} - {$e->getMessage()}");

            return null;
        }
    }

    private function loadIndex(array $config, bool $preferLocal): ?array
    {
        return $preferLocal ? $this->loadLocalIndex($config) : $this->loadRemoteIndex($config);
    }

    private function loadLocalIndex(array $config): ?array
    {
        $indexPath = $this->localIndexPath($config);
        if (!$indexPath) {
            // Bake was preferred but unreadable; do not pretend a remote fetch succeeded via stale data.
            return null;
        }

        $data = json_decode(file_get_contents($indexPath), true);
        if (is_array($data)) {
            return $data;
        }

        Log::warning("[SyncDefaultTemplates] Failed to parse local template index: {$indexPath}");

        return null;
    }

    private function loadRemoteIndex(array $config): ?array
    {
        $url = $config['base_url'] . $config['template_repo'] . '/' . $config['template_branch'] . '/index.json';
        try {
            $response = Http::timeout(10)->get($url);
            if (!$response->successful()) {
                Log::warning("[SyncDefaultTemplates] Failed to fetch template index from GitHub: {$url} - Status: {$response->status()}");

                return null;
            }

            return $response->json();
        } catch (ConnectionException $e) {
            Log::warning("[SyncDefaultTemplates] Timed out fetching template index from GitHub: {$url} - {$e->getMessage()}");

            return null;
        }
    }

    private function loadTemplatePayload(array $config, array $template, bool $preferLocal): ?array
    {
        $relativePath = ltrim($template['relative_path'] ?? '', './');
        if ($relativePath === '') {
            return null;
        }

        return $preferLocal
            ? $this->loadLocalTemplate($config, $relativePath)
            : $this->loadRemoteTemplate($config, $template, $relativePath);
    }

    private function loadLocalTemplate(array $config, string $relativePath): ?array
    {
        $base = $this->localPath($config);
        $localFile = $base ? $base . '/' . $relativePath : null;
        if (!$localFile || !is_file($localFile)) {
            return null;
        }

        $payload = json_decode(file_get_contents($localFile), true);
        if (is_array($payload)) {
            return $payload;
        }

        Log::warning("[SyncDefaultTemplates] Failed to parse local template: {$localFile}");

        return null;
    }

    private function loadRemoteTemplate(array $config, array $template, string $relativePath): ?array
    {
        $url = $config['base_url'] . $config['template_repo'] . '/' . $config['template_branch'] . '/' . $relativePath;
        try {
            $response = Http::timeout(10)->get($url);
            if (!$response->successful()) {
                Log::warning("[SyncDefaultTemplates] Skipped template due to failed fetch: {$template['name']} ({$template['uuid']}) - Status: {$response->status()}");

                return null;
            }

            return $response->json();
        } catch (ConnectionException $e) {
            Log::warning("[SyncDefaultTemplates] Skipped template due to failed fetch: {$template['name']} ({$template['uuid']}) - {$e->getMessage()}");

            return null;
        }
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
