<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mime\MimeTypes;

class StorageController extends Controller
{
    /**
     * Serve files from the public storage disk.
     * This controller handles all storage requests, including tenant paths.
     *
     * @param Request $request
     * @param string $path The path to the file in storage/app/public
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
     * @throws NotFoundHttpException
     * @throws AccessDeniedHttpException
     */
    public function serve(Request $request, string $path)
    {
        // Prevent directory traversal attacks
        $path = str_replace(['..', './'], '', $path);

        $tenant = app('currentTenant');
        $tenantId = $tenant ? $tenant->id : null;

        // check if the path is a tenant path
        if (preg_match('/^tenant_\d+\//', $path)) {
            $path = str_replace('tenant_' . $tenantId . '/', '', $path);
        } else {
            $path = 'tenant_' . $tenantId . '/' . $path;
        }

        // Get the full path to the file
        $fullPath = Storage::disk('public')->path($path);

        // Check if file exists
        if (!Storage::disk('public')->exists($path)) {
            throw new NotFoundHttpException('File not found');
        }

        // Check if it's a file (not a directory)
        if (!is_file($fullPath)) {
            throw new NotFoundHttpException('Not a file');
        }

        // Get file mime type using Symfony's MimeTypes
        $mimeTypes = new MimeTypes();
        $mimeType = $mimeTypes->guessMimeType($fullPath) ?? 'application/octet-stream';

        // Get file size
        $size = Storage::disk('public')->size($path);

        // Get last modified time
        $lastModified = Storage::disk('public')->lastModified($path);

        // Create response
        $response = response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Length' => $size,
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
            'Cache-Control' => 'public, max-age=604800', // Cache for 1 week
        ]);

        return $response;
    }
}
