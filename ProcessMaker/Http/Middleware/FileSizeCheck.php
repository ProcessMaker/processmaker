<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FileSizeCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // dd($request);

        if ($request->hasFile('file')) {
            // dd('here');
            // dd('hit middleware');
            // Get upload_max_filesize from php.ini
            $maxFileSize = ini_get('upload_max_filesize');

            // Convert Max File Size to bytes
            $convertedMaxFileSize = $this->convertToBytes($maxFileSize);

            // Get the uploaded file
            $uploadedFile = $request->file('file');

            // If the request has a total size value, get the pre-chunked total size. Else, get the file size.
            $totalSize = $request->get('totalSize') ? $request->get('totalSize') : $uploadedFile->getSize();

            // If the file size is larger than the upload_max_filesize, throw an error
            if ($totalSize > $convertedMaxFileSize) {
                return response()->json([
                    'message' => 'The file is too large. Maximum allowed size is ' . $maxFileSize,
                ], 422);
            }

            return $next($request);
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
}
