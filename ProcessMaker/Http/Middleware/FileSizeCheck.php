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
            $maxFileSize = ini_get('upload_max_filesize');
            $convertedMaxFileSize = $this->convertToBytes($maxFileSize);

            try {
                $this->validateFiles($request, $convertedMaxFileSize, $maxFileSize);
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
     * @param int $maxSize
     * @param string $maxSizeDisplay
     * @throws ValidationException
     */
    private function validateFiles($request, $maxSize, $maxSizeDisplay)
    {
        $files = $request->allFiles();
        $totalSize = (int) $request->get('totalSize', 0);

        // Check total size first if it exists
        if ($totalSize > 0 && $totalSize > $maxSize) {
            throw ValidationException::withMessages([
                'file' => ['The file is too large. Maximum allowed size is ' . $maxSizeDisplay],
            ]);
        }

        // If no total size, check individual files
        foreach ($files as $file) {
            if (!$file->isValid()) {
                throw ValidationException::withMessages([
                    'file' => ['The file upload was not successful.'],
                ]);
            }

            if ($totalSize == 0 && $file->getSize() > $maxSize) {
                throw ValidationException::withMessages([
                    'file' => ['The file is too large. Maximum allowed size is ' . $maxSizeDisplay],
                ]);
            }
        }
    }
}
