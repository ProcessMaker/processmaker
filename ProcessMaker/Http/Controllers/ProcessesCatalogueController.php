<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;

class ProcessesCatalogueController extends Controller
{
    public function index(Request $request, Process $process = null)
    {
        return view('processes-catalogue.index', compact('process'));
    }
}
