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
        $request->validate(DataSourceIntegrations::rules());
        try {
            $dataSourceIntegration = DataSourceIntegrations::create($request->all());

            return response()->json(['message' => 'Integration created successfully'], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            Log::error('Database error creating integration: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to save integration to the database',
            ], 500);
        } catch (Exception $e) {
            Log::error('Unexpected error creating integration: ' . $e->getMessage());

            return response()->json([
                'error' => 'An unexpected error occurred',
            ], 500);
        }
    }
}
