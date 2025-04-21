<?php

namespace ProcessMaker\Http\Controllers\Api;

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\DataSourceIntegrations;
use ProcessMaker\Services\DataSourceIntegrations\DataSourceIntegrationsService;

class DataSourceIntegrationsController extends Controller
{
    protected DataSourceIntegrationsService $service;

    public function __construct(DataSourceIntegrationsService $service)
    {
        $this->service = $service;
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate(DataSourceIntegrations::rules());

        try {
            DataSourceIntegrations::create($validatedData);

            return response()->json([
                'message' => 'Integration created successfully',
            ], 201);
        } catch (QueryException $e) {
            Log::error('Database error while creating integration', [
                'message' => $e->getMessage(),
                'input' => $request->only(['name', 'key', 'auth_type']),
            ]);

            return response()->json([
                'error' => 'Failed to save integration to the database',
            ], 500);
        } catch (Exception $e) {
            Log::error('Unexpected error while creating integration', [
                'message' => $e->getMessage(),
                'input' => $request->only(['name', 'key', 'auth_type']),
            ]);

            return response()->json([
                'error' => 'An unexpected error occurred',
            ], 500);
        }
    }

    public function getParameters(Request $request)
    {
        if ($request->input('source')) {
            return $this->service->setSource($request->input('source'))->getParameters();
        }

        return $this->service->getParameters();
    }

    public function getCompanies(Request $request)
    {
        return $this->service->setSource($request->input('source'))->getCompanies();
    }

    public function fetchCompanyDetails(Request $request)
    {
        return $this->service->fetchCompanyDetails();
    }
}
