<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Services\VibeAiService;
use Symfony\Component\HttpFoundation\Response;

class VibeProjectController extends Controller
{
    /**
     * Raw Vue/JS/YAML file content must not pass through SanitizeInput.
     *
     * @var array<int, string>
     */
    public $doNotSanitize = ['*'];

    private function projectRoot(): string
    {
        $override = env('VIBE_PROJECT_ROOT');
        if (is_string($override) && $override !== '') {
            $resolved = realpath($override);
            if ($resolved !== false && is_dir($resolved)) {
                return $resolved;
            }
        }

        return base_path('node_modules/@processmaker/screen-builder/src/vibe-project');
    }

    private function resolvePath(?string $relativePath, bool $mustExist = true): string
    {
        if ($relativePath === null || $relativePath === '') {
            abort(Response::HTTP_BAD_REQUEST, 'path is required');
        }

        $projectRoot = $this->projectRoot();
        if (!is_dir($projectRoot)) {
            mkdir($projectRoot, 0755, true);
        }

        $root = realpath($projectRoot);
        if ($root === false) {
            abort(Response::HTTP_NOT_FOUND, 'vibe-project directory not found');
        }

        $normalized = str_replace('\\', '/', $relativePath);
        $normalized = ltrim($normalized, './');

        if ($normalized === '' || preg_match('#(^|/)\.\.(/|$)#', $normalized)) {
            abort(Response::HTTP_BAD_REQUEST, 'Invalid path');
        }

        $fullPath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);

        if (file_exists($fullPath)) {
            $resolved = realpath($fullPath);
            if ($resolved === false || !str_starts_with($resolved, $root)) {
                abort(Response::HTTP_BAD_REQUEST, 'Invalid path');
            }

            return $resolved;
        }

        if (!str_starts_with($fullPath, $root . DIRECTORY_SEPARATOR)) {
            abort(Response::HTTP_BAD_REQUEST, 'Invalid path');
        }

        $parent = dirname($fullPath);
        while (!file_exists($parent) && $parent !== $root && dirname($parent) !== $parent) {
            $parent = dirname($parent);
        }

        $realParent = realpath($parent);
        if ($realParent === false || !str_starts_with($realParent, $root)) {
            abort(Response::HTTP_BAD_REQUEST, 'Invalid path');
        }

        if ($mustExist) {
            abort(
                is_dir($fullPath) ? Response::HTTP_BAD_REQUEST : Response::HTTP_NOT_FOUND,
                is_dir($fullPath) ? 'Invalid path' : 'File not found'
            );
        }

        return $fullPath;
    }

    private function resolveExistingPath(?string $relativePath): ?string
    {
        if ($relativePath === null || $relativePath === '') {
            return null;
        }

        $projectRoot = $this->projectRoot();
        if (!is_dir($projectRoot)) {
            mkdir($projectRoot, 0755, true);
        }

        $root = realpath($projectRoot);
        if ($root === false) {
            return null;
        }

        $normalized = str_replace('\\', '/', $relativePath);
        $normalized = ltrim($normalized, './');

        if ($normalized === '' || preg_match('#(^|/)\.\.(/|$)#', $normalized)) {
            return null;
        }

        $fullPath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
        if (!file_exists($fullPath)) {
            return null;
        }

        $resolved = realpath($fullPath);

        return $resolved !== false && str_starts_with($resolved, $root) ? $resolved : null;
    }

    private function buildTree(string $dir, string $relativeBase = ''): array
    {
        $nodes = [];
        $entries = scandir($dir) ?: [];

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || str_starts_with($entry, '.')) {
                continue;
            }

            $relPath = $relativeBase === '' ? $entry : $relativeBase . '/' . $entry;
            $fullPath = $dir . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($fullPath)) {
                $nodes[] = [
                    'name' => $entry,
                    'path' => $relPath,
                    'type' => 'directory',
                    'children' => $this->buildTree($fullPath, $relPath),
                ];
            } else {
                $nodes[] = [
                    'name' => $entry,
                    'path' => $relPath,
                    'type' => 'file',
                ];
            }
        }

        usort($nodes, function ($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'directory' ? -1 : 1;
            }

            return strcmp($a['name'], $b['name']);
        });

        return $nodes;
    }

    public function tree(): JsonResponse
    {
        $root = $this->projectRoot();
        if (!is_dir($root)) {
            mkdir($root, 0755, true);
        }

        return response()->json(['tree' => $this->buildTree($root)]);
    }

    public function read(Request $request): JsonResponse
    {
        $path = $request->query('path');
        if ($path === null || $path === '') {
            return response()->json(['error' => 'path is required'], Response::HTTP_BAD_REQUEST);
        }

        $fullPath = $this->resolveExistingPath($path);
        if ($fullPath === null || !is_file($fullPath)) {
            return response()->json(
                ['error' => "File not found: {$path}"],
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json([
            'path' => $path,
            'content' => file_get_contents($fullPath),
        ]);
    }

    public function write(Request $request): JsonResponse
    {
        $path = $request->input('path');
        $content = $request->input('content', '');
        $fullPath = $this->resolvePath($path, false);

        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($fullPath, $content);

        return response()->json(['path' => $path, 'saved' => true]);
    }

    public function mkdir(Request $request): JsonResponse
    {
        $path = $request->input('path');
        $fullPath = $this->resolvePath($path, false);

        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        return response()->json(['path' => $path, 'created' => true]);
    }

    public function delete(Request $request): JsonResponse
    {
        $path = $request->query('path');
        $fullPath = $this->resolvePath($path);

        if (!file_exists($fullPath)) {
            return response()->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        if (is_dir($fullPath)) {
            $this->removeDirectory($fullPath);
        } else {
            unlink($fullPath);
        }

        return response()->json(['path' => $path, 'deleted' => true]);
    }

    public function move(Request $request): JsonResponse
    {
        $from = $request->input('from');
        $to = $request->input('to');

        if (!$from || !$to) {
            return response()->json(['error' => 'from and to are required'], Response::HTTP_BAD_REQUEST);
        }

        $sourcePath = $this->resolveExistingPath($from);
        if ($sourcePath === null) {
            return response()->json(['error' => "Not found: {$from}"], Response::HTTP_NOT_FOUND);
        }

        $destinationPath = $this->resolvePath($to, false);
        if (file_exists($destinationPath)) {
            return response()->json(['error' => "Already exists: {$to}"], Response::HTTP_CONFLICT);
        }

        $destinationDir = dirname($destinationPath);
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        rename($sourcePath, $destinationPath);

        return response()->json(['from' => $from, 'to' => $to, 'moved' => true]);
    }

    public function duplicate(Request $request): JsonResponse
    {
        $path = $request->input('path');
        $to = $request->input('to');

        if (!$path) {
            return response()->json(['error' => 'path is required'], Response::HTTP_BAD_REQUEST);
        }

        $sourcePath = $this->resolveExistingPath($path);
        if ($sourcePath === null) {
            return response()->json(['error' => "Not found: {$path}"], Response::HTTP_NOT_FOUND);
        }

        if (!$to) {
            $to = $this->buildDuplicatePath($path);
        }

        $destinationPath = $this->resolvePath($to, false);
        if (file_exists($destinationPath)) {
            return response()->json(['error' => "Already exists: {$to}"], Response::HTTP_CONFLICT);
        }

        $destinationDir = dirname($destinationPath);
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        if (is_dir($sourcePath)) {
            $this->copyDirectory($sourcePath, $destinationPath);
        } else {
            copy($sourcePath, $destinationPath);
        }

        return response()->json([
            'from' => $path,
            'path' => $to,
            'duplicated' => true,
        ]);
    }

    public function export(): JsonResponse
    {
        $root = $this->projectRoot();
        $files = [];
        $this->collectFiles($root, $root, $files);

        return response()->json(['files' => $files]);
    }

    public function import(Request $request): JsonResponse
    {
        $files = $request->input('files', []);

        if (!is_array($files)) {
            return response()->json(['error' => 'files array is required'], Response::HTTP_BAD_REQUEST);
        }

        foreach ($files as $file) {
            $path = $file['path'] ?? null;
            $content = $file['content'] ?? '';
            $fullPath = $this->resolvePath($path, false);
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($fullPath, $content);
        }

        return response()->json(['imported' => count($files)]);
    }

    public function aiConfig(): JsonResponse
    {
        return response()->json(VibeAiService::publicConfig());
    }

    public function aiChat(Request $request): JsonResponse
    {
        set_time_limit(300);
        ini_set('max_execution_time', '300');

        try {
            return response()->json(VibeAiService::chat($request->all()));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    private function buildDuplicatePath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $parts = explode('/', $normalized);
        $fileName = array_pop($parts);
        $dot = strrpos($fileName, '.');
        $base = $dot === false ? $fileName : substr($fileName, 0, $dot);
        $ext = $dot === false ? '' : substr($fileName, $dot);

        return implode('/', $parts) . '/' . $base . 'Copy' . $ext;
    }

    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        foreach (scandir($source) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $sourcePath = $source . DIRECTORY_SEPARATOR . $entry;
            $destinationPath = $destination . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destinationPath);
            } else {
                copy($sourcePath, $destinationPath);
            }
        }
    }

    private function collectFiles(string $dir, string $root, array &$files): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..' || str_starts_with($entry, '.')) {
                continue;
            }

            $fullPath = $dir . DIRECTORY_SEPARATOR . $entry;
            $relPath = ltrim(substr($fullPath, strlen($root)), DIRECTORY_SEPARATOR);

            if (is_dir($fullPath)) {
                $this->collectFiles($fullPath, $root, $files);
            } else {
                $files[] = [
                    'path' => str_replace(DIRECTORY_SEPARATOR, '/', $relPath),
                    'content' => file_get_contents($fullPath),
                ];
            }
        }
    }

    private function removeDirectory(string $dir): void
    {
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
