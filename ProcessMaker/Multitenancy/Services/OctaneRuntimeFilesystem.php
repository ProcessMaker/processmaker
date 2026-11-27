<?php

declare(strict_types=1);

namespace ProcessMaker\Multitenancy\Services;

use Illuminate\Support\Facades\File;

/**
 * Writes and removes Octane supervisor/Caddy paths under configured directories.
 */
class OctaneRuntimeFilesystem
{
    public function supervisorPath(string $filename): string
    {
        return rtrim((string) config('octane_tenants.supervisor_dir'), '/') . '/' . $filename;
    }

    public function singleIniName(): string
    {
        return OctaneRuntimeRenderer::SINGLE_PROGRAM . '.ini';
    }

    public function edgeIniName(): string
    {
        return OctaneRuntimeRenderer::EDGE_PROGRAM . '.ini';
    }

    public function tenantIniName(int $tenantId): string
    {
        return 'octane-tenant-' . $tenantId . '.ini';
    }

    /**
     * @return list<string>
     */
    public function existingTenantIniPaths(): array
    {
        $dir = config('octane_tenants.supervisor_dir');
        if (!is_dir($dir)) {
            return [];
        }

        return array_values(array_filter(
            glob(rtrim($dir, '/') . '/octane-tenant-*.ini') ?: [],
            'is_file'
        ));
    }

    /**
     * @param  list<string>  $paths
     * @return list<string>
     */
    public function removeRuntimeFiles(array $paths, bool $dryRun): array
    {
        $removed = [];
        foreach (array_unique($paths) as $path) {
            if (!$path || !is_file($path)) {
                continue;
            }
            if (!$dryRun) {
                File::delete($path);
            }
            $removed[] = $path;
        }

        return $removed;
    }

    public function writeFile(string $path, string $contents, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }

        $directory = dirname($path);
        if (!is_dir($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($path, $contents);
    }

    public function ensureSupervisorDirectory(bool $dryRun): void
    {
        $dir = config('octane_tenants.supervisor_dir');
        if (is_dir($dir)) {
            return;
        }

        if ($dryRun) {
            return;
        }

        File::makeDirectory($dir, 0755, true);
    }
}