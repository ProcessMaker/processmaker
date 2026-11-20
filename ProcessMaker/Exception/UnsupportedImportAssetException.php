<?php

namespace ProcessMaker\Exception;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class UnsupportedImportAssetException extends RuntimeException
{
    public function render(): JsonResponse
    {
        return new JsonResponse(['error' => $this->getMessage(), 'message' => $this->getMessage()], 422);
    }
}
