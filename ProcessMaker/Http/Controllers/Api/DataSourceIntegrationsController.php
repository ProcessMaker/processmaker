<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
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
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
