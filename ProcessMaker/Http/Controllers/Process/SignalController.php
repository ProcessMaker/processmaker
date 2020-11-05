<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\Factory;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Traits\HasControllerAddons;
use ProcessMaker\Http\Controllers\Controller;

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

    /**
     * Get a specific signal
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function edit($id)
    {
        $signal = SignalManager::findSignal($id);

        return view('processes.signals.edit', compact('signal'));
    }
}
