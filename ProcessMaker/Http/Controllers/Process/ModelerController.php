<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Http\Request;
use ProcessMaker\Events\ModelerStarting;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Managers\ModelerManager;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\Process;
use ProcessMaker\PackageHelper;
use ProcessMaker\Traits\HasControllerAddons;

class ModelerController extends Controller
{
    use HasControllerAddons;

    /**
     * Invokes the Process Modeler for rendering.
     */
    public function show(ModelerManager $manager, Process $process, Request $request)
    {
        /*
         * Emit the ModelerStarting event, passing in our ModelerManager instance. This will
         * allow packages to add additional javascript for modeler initialization which
         * can customize the modeler controls list.
         */
        event(new ModelerStarting($manager));

        $draft = $process->versions()->draft()->first();
        if ($draft) {
            $process->fill($draft->only(['svg', 'bpmn']));
        }

        return view('processes.modeler.index', [
            'process' => $process->append('notifications', 'task_notifications'),
            'manager' => $manager,
            'signalPermissions' => SignalManager::permissions($request->user()),
            'isVersionsInstalled' => PackageHelper::isPackageInstalled('ProcessMaker\Package\Versions\PluginServiceProvider'),
        ]);
    }
}
