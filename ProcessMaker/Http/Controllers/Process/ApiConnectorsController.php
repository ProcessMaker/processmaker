<?php

namespace ProcessMaker\Http\Controllers\Process;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ApiConnector;

class ApiConnectorsController extends Controller
{
    /**
     * Get the list of api connector
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('processes.api-connectors.index');
    }

    /**
     * Get a specific Api Connector
     *
     * @param ApiConnector $apiConnector
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(ApiConnector $apiConnector)
    {
        return view('processes.api-connectors.edit', compact('apiConnector'));
    }

}
