<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class FileSizeCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->allFiles()) {
            try {
                $this->validateFiles($request);
            } catch (ValidationException $e) {
                return response()->json([
                    'message' => $e->errors()['file'][0],
                ])->setStatusCode(422);
            } catch (Exception $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                ])->setStatusCode(422);
            }
        }

        return $next($request);
    }

    /**
     * Handle tasks after the response is sent.
     */
    public function terminate(Request $request, $response): void
    {
        // Suppress unused parameter warning.
        unset($request);

        if ($response instanceof Response) {
            $response->headers->set('X-FileSize-Checked', 'true');
        }
    }

    /**
     * Get the maximum file size allowed
     *
     * @param string $filetype
     * @return string
     */
    protected function getMaxFileSize($filetype)
    {
        // Define image types
        $imageMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'image/bmp',
            'image/tiff',
        ];

        // Define document types
        $documentMimeTypes = [
            'text/plain',
            'application/rtf',
            'text/markdown',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/pdf',
            'text/csv',
            'text/html',
            'application/xml',
            'text/xml',
            'application/json',
        ];

        if (in_array($filetype, $imageMimeTypes)) {
            return config('app.settings.img_max_filesize_limit');
        } elseif (in_array($filetype, $documentMimeTypes)) {
            return config('app.settings.doc_max_filesize_limit');
        } else {
            return config('app.settings.max_filesize_limit') ?? ini_get('upload_max_filesize');
        }
    }

    /**
     * Convert PHP ini size value to bytes
     *
     * @param string $size
     * @return int
     */
    private function convertToBytes($size)
    {
        $unit = strtoupper(substr($size, -1));
        $value = (int) substr($size, 0, -1);

        switch ($unit) {
            case 'G':
                $value *= 1024 * 1024 * 1024; // Convert GB to bytes
                break;
            case 'M':
                $value *= 1024 * 1024; // Convert MB to bytes
                break;
            case 'K':
                $value *= 1024; // Convert KB to bytes
                break;
            default:
                return (int) $size; // Already in bytes
        }

        return $value;
    }

    /**
     * Recursively validate files
     *
     * @param Request $request
     * @throws ValidationException
     */
    private function validateFiles($request)
    {
        $files = $request->allFiles();
        $totalSize = (int) $request->get('totalSize', 0);
        $maxSize = config('app.settings.max_filesize_limit');
        $maxSizeInBytes = $this->convertToBytes($maxSize);

        // Check total size first if it exists (using a general max_filesize_limit from env)
        if ($totalSize > 0 && $totalSize > $maxSizeInBytes) {
            throw ValidationException::withMessages([
                'file' => ['The total upload size is too large. Maximum allowed size is ' . $maxSize],
            ]);
        }

        // If no total size, check individual files
        foreach ($files as $file) {
            if (!$file->isValid()) {
                throw ValidationException::withMessages([
                    'file' => ['The file upload was not successful.'],
                ]);
            }

            // Get max filesize depending on filetype
            $fileType = $file->getClientMimeType();
            $maxFileSize = $this->getMaxFileSize($fileType);
            $maxFileSizeInBytes = $this->convertToBytes($maxFileSize);

            if ($totalSize == 0 && $file->getSize() > $maxFileSizeInBytes) {
                throw ValidationException::withMessages([
                    'file' => ['The file is too large. Maximum allowed size is ' . $maxFileSize],
                ]);
            }
        }
    }
}
