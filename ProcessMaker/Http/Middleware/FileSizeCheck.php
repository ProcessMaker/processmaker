<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        // Get upload_max_filesize from php.ini
        $maxFileSize = ini_get('upload_max_filesize');
        $convertedMaxFileSize = $this->convertToBytes();
        $totalSize = $request->get('totalSize');

        if ($totalSize > $convertedMaxFileSize) {
            return response()->json([
                'message' => 'The file is too large. Maximum allowed size is ' . $maxFileSize,
            ], 422);
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
