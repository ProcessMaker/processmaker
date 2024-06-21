<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Services\JweService;

class ProcessIntelligenceController extends Controller
{
    protected $jweService;

    public function __construct(JweService $jweService)
    {
        $this->jweService = $jweService;
    }

    /**
     * Retrieves a JWE token.
     */
    public function getJweToken(): JsonResponse
    {
        $additionalData = [];
        $jweToken = $this->jweService->generateToken($additionalData);

        return response()->json([
            'token' => $jweToken,
        ]);
    }
}
