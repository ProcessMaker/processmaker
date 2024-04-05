<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Process;
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
        $launchpad = null;
        if (!is_null($process)) {
            $launchpad = ProcessLaunchpad::getLaunchpad(true, $process->id);
        }
        $currentUser = Auth::user()->only(['id', 'username', 'fullname', 'firstname', 'lastname', 'avatar']);
        return view('processes-catalogue.index', compact('process' , 'launchpad', 'currentUser'));
    }
}
