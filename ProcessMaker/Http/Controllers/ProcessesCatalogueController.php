<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Events\ScreenBuilderStarting;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Managers\ScreenBuilderManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Traits\HasControllerAddons;

/**
 * @param Request $request
 * @param Process $process
 *
 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
 */
class ProcessesCatalogueController extends Controller
{
    use HasControllerAddons;
    
    public function index(Request $request, Process $process = null)
    {
        $manager = app(ScreenBuilderManager::class);
        event(new ScreenBuilderStarting($manager, 'DISPLAY'));
        $currentUser = Auth::user()->only(['id', 'username', 'fullname', 'firstname', 'lastname', 'avatar']);
        return view('processes-catalogue.index', compact('process', 'currentUser', 'manager'));
    }
}
