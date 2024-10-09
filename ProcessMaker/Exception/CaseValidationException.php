<?php

namespace ProcessMaker\Exception;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaseValidationException extends Exception
{
    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }
}
