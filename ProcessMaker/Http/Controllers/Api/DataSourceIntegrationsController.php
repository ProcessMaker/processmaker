<?php

namespace ProcessMaker\Http\Controllers\Api;

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\DataSourceIntegrations;

class DataSourceIntegrationsController extends Controller
{
    public function store(Request $request)
    {
        $validatedData = $request->validate(DataSourceIntegrations::rules());

        try {
            $integration = DataSourceIntegrations::create($validatedData);

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
}
