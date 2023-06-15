<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Http\Request;
use ProcessMaker\Events\ModelerStarting;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Managers\ModelerManager;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
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
            'autoSaveDelay' => config('versions.delay.process', 5000),
            'isVersionsInstalled' => PackageHelper::isPackageInstalled('ProcessMaker\Package\Versions\PluginServiceProvider'),
            'isDraft' => $draft !== null,
        ]);
    }

    /**
     * Invokes the Modeler for In-flight Process Map rendering.
     */
    public function inflight(ModelerManager $manager, Process $process, Request $request)
    {
        event(new ModelerStarting($manager));

        $bpmn = $process->bpmn;
        $requestCompletedNodes = [];
        $requestInProgressNodes = [];

        // Use the process version that was active when the request was started.
        $processRequest = ProcessRequest::find($request->request_id);
        if ($processRequest) {
            $bpmn = $process->versions()
                ->where('id', $processRequest->process_version_id)
                ->firstOrFail()
                ->bpmn;

            $requestCompletedNodes = $processRequest->tokens()->where('status', 'CLOSED')->pluck('element_id');
            $requestInProgressNodes = $processRequest->tokens()->where('status', 'ACTIVE')->pluck('element_id');
            // Remove any node that is 'ACTIVE' from the 'CLOSED' list.
            $filteredCompletedNodes = $requestCompletedNodes->diff($requestInProgressNodes)->values();
        }

        return view('processes.modeler.inflight', [
            'manager' => $manager,
            'process' => $process,
            'bpmn' => $bpmn,
            'requestCompletedNodes' => $filteredCompletedNodes,
            'requestInProgressNodes' => $requestInProgressNodes,
        ]);
    }

    /**
     * Invokes the Modeler for In-flight Process Map rendering for ai generative.
     */
    public function inflightProcessAi(ModelerManager $manager, Process $process, Request $request)
    {
        // Receive the history ID
        // Call microservice and pass the history ID
        // The microervice returns a history entry that contains [created_at, id, prompt_id, prompt, bpmn, user_id]
        // Use the bpmn and return to view
        event(new ModelerStarting($manager));
        $bpmn = $process->bpmn;

        return view('processes.modeler.inflight-generative-ai', [
            'manager' => $manager,
            'bpmn' => $bpmn,
        ]);
    }
}
