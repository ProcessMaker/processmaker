<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Process;
use ProcessMaker\Managers\ModelerManager;
use ProcessMaker\Events\ModelerStarting;
use ProcessMaker\Managers\SignalManager;

class ModelerController extends Controller
{

    /**
     * Invokes the Process Modeler for rendering.
     */
    public function __invoke(ModelerManager $manager, Process $process, Request $request)
    {
        /**
         * Emit the ModelerStarting event, passing in our ModelerManager instance. This will 
         * allow packages to add additional javascript for modeler initialization which
         * can customize the modeler controls list.
         */
        event(new ModelerStarting($manager));
        return view('processes.modeler.index', [
            'process' => $process->append('notifications', 'task_notifications'),
            'manager' => $manager,
            'signalPermissions' => SignalManager::permissions($request->user()),
        ]);
    }
}