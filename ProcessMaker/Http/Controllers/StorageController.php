<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mime\MimeTypes;

class StorageController extends Controller
{
    private const CACHE_MAX_AGE = 604800; // 1 week in seconds

    /**
     * Serve files from the public storage disk.
     * This controller handles all storage requests, including tenant paths.
     *
     * @param Request $request
     * @param string $path The path to the file in storage/app/public
     *
     * @return BinaryFileResponse
     * @throws NotFoundHttpException
     * @throws AccessDeniedHttpException
     */
    public function serve(Request $request, string $path): BinaryFileResponse
    {
        try {
            // Validate and sanitize the path
            $path = $this->validateAndSanitizePath($path);

            // Get tenant context
            $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;

            if ($tenant) {
                $tenantId = $tenant->id;

                // Handle tenant path
                $path = $this->handleTenantPath($path, $tenantId);
            }

            // Get storage disk
            // If there were no tenant, the default storage disk is used
            $disk = Storage::disk('public');

            // Validate file exists and is accessible
            if (!$this->validateFile($disk, $path)) {
                throw new NotFoundHttpException('File not found or not accessible');
            }

            // Get file metadata
            $metadata = $this->getFileMetadata($disk, $path);

            return $this->createFileResponse($metadata);
        } catch (NotFoundHttpException | AccessDeniedHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error serving file', [
                'path' => $path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new NotFoundHttpException('Error serving file');
        }
    }

    /**
     * Validate and sanitize the file path
     *
     * @param string $path
     *
     * @return string
     * @throws NotFoundHttpException
     */
    private function validateAndSanitizePath(string $path): string
    {
        // Remove any directory traversal attempts
        $path = str_replace(['..', './'], '', $path);

        // Ensure path is not empty and doesn't start with a slash
        $path = trim($path, '/');

        if (empty($path)) {
            throw new NotFoundHttpException('Invalid file path');
        }

        return $path;
    }

    /**
     * Handle tenant-specific path logic
     *
     * @param string $path
     * @param int $tenantId
     *
     * @return string
     */
    private function handleTenantPath(string $path, int $tenantId): string
    {
        if (preg_match('/^tenant_\d+\//', $path)) {
            return str_replace('tenant_' . $tenantId . '/', '', $path);
        }

        return 'tenant_' . $tenantId . '/' . $path;
    }

    /**
     * Validate file exists and is accessible
     *
     * @param \Illuminate\Filesystem\FilesystemAdapter $disk
     * @param string $path
     *
     * @return bool
     */
    private function validateFile($disk, string $path): bool
    {
        if (!$disk->exists($path)) {
            return false;
        }

        $fullPath = $disk->path($path);

        return is_file($fullPath) && is_readable($fullPath);
    }

    /**
     * Get file metadata
     *
     * @param \Illuminate\Filesystem\FilesystemAdapter $disk
     * @param string $path
     *
     * @return array
     */
    private function getFileMetadata($disk, string $path): array
    {
        $fullPath = $disk->path($path);
        $mimeTypes = new MimeTypes();

        return [
            'path' => $fullPath,
            'mime_type' => $mimeTypes->guessMimeType($fullPath) ?? 'application/octet-stream',
            'size' => $disk->size($path),
            'last_modified' => $disk->lastModified($path),
        ];
    }

    /**
     * Create file response with proper headers
     */
    private function createFileResponse(array $metadata): BinaryFileResponse
    {
        $response = response()->file($metadata['path'], [
            'Content-Type' => $metadata['mime_type'],
            'Content-Length' => $metadata['size'],
            'Last-Modified' => gmdate('D, d M Y H:i:s', $metadata['last_modified']) . ' GMT',
            'Cache-Control' => 'public, max-age=' . self::CACHE_MAX_AGE,
        ]);

        // Add security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Content-Disposition', 'inline');

        return $response;
    }
}
