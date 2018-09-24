<?php

namespace ProcessMaker\Http\Controllers\Management;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;

class EnvironmentVariablesController extends Controller
{
    /**
     * Get the list of environment variables
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function index()
    {
        return view('processes.environment-variables.index');
    }

}
