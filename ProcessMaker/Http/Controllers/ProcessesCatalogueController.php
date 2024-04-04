<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessLaunchpad;
use ProcessMaker\Models\ProcessCategory;

/**
 * @param Request $request
 * @param Process $process
 *
 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
 */
class ProcessesCatalogueController extends Controller
{
    public function index(Request $request, Process $process = null)
    {
        $processLaunchpad = new ProcessLaunchpad();
        $launchpad = $processLaunchpad::getLaunchpad(true, $process->id);
        return view('processes-catalogue.index', compact('process', 'launchpad'));
    }
}
