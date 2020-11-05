<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Traits\HasControllerAddons;

class SignalController extends Controller
{
    use HasControllerAddons;

    /**
     * Get the list of signals
     *
     * @param Request $request
     * 
     * @return Factory|View
     */
    public function index(Request $request)
    {
        return view('processes.signals.index');
    }
}
