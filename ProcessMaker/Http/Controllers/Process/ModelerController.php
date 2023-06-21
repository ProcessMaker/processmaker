<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
    public function inflightProcessAi(ModelerManager $manager, $promptVersionId, Request $request)
    {
        $url = 'pm/getPromptVersion';
        $headers = [
            'Authorization' => 'token',
        ];

        $params = [
            'promptVersionId' => $promptVersionId,
        ];

        // $promptVersion = Http::withHeaders($headers)->post($url, $params);

        event(new ModelerStarting($manager));
        // $bpmn = $promptVersion->json()->bpmn;

        $bpmn = '<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL" xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" xmlns:di="http://www.omg.org/spec/DD/20100524/DI" xmlns:pm="http://processmaker.com/BPMN/2.0/Schema.xsd" xmlns:tns="http://sourceforge.net/bpmn/definitions/_1530553328908" xmlns:xsd="http://www.w3.org/2001/XMLSchema" targetNamespace="http://bpmn.io/schema/bpmn" exporter="ProcessMaker Modeler" exporterVersion="1.0" xsi:schemaLocation="http://www.omg.org/spec/BPMN/20100524/MODEL http://bpmn.sourceforge.net/schemas/BPMN20.xsd">
  <bpmn:process id="ProcessId" name="ProcessName" isExecutable="true">
    <bpmn:startEvent id="node_1" name="Start Event" pm:allowInterstitial="false">
      <bpmn:outgoing>node_18</bpmn:outgoing>
    </bpmn:startEvent>
    <bpmn:task id="node_2" name="Fill email" pm:allowInterstitial="false" pm:assignment="requester" pm:assignmentLock="false" pm:allowReassignment="false">
      <bpmn:incoming>node_18</bpmn:incoming>
      <bpmn:outgoing>node_26</bpmn:outgoing>
    </bpmn:task>
    <bpmn:endEvent id="node_10" name="End Event">
      <bpmn:incoming>node_26</bpmn:incoming>
    </bpmn:endEvent>
    <bpmn:sequenceFlow id="node_18" sourceRef="node_1" targetRef="node_2" />
    <bpmn:sequenceFlow id="node_26" sourceRef="node_2" targetRef="node_10" />
  </bpmn:process>
  <bpmndi:BPMNDiagram id="BPMNDiagramId">
    <bpmndi:BPMNPlane id="BPMNPlaneId" bpmnElement="ProcessId">
      <bpmndi:BPMNShape id="node_1_di" bpmnElement="node_1">
        <dc:Bounds x="255" y="280" width="36" height="36" />
      </bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="node_2_di" bpmnElement="node_2">
        <dc:Bounds x="350" y="260" width="116" height="76" />
      </bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="node_10_di" bpmnElement="node_10">
        <dc:Bounds x="506" y="280" width="36" height="36" />
      </bpmndi:BPMNShape>
      <bpmndi:BPMNEdge id="node_18_di" bpmnElement="node_18">
        <di:waypoint x="273" y="298" />
        <di:waypoint x="408" y="298" />
      </bpmndi:BPMNEdge>
      <bpmndi:BPMNEdge id="node_26_di" bpmnElement="node_26">
        <di:waypoint x="408" y="298" />
        <di:waypoint x="524" y="298" />
      </bpmndi:BPMNEdge>
    </bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>
</bpmn:definitions>';

        $bpmn1 = Process::find(43)->bpmn;

        // dd($bpmn, $bpmn1);
        return view('processes.modeler.inflight-generative-ai', [
            'manager' => $manager,
            'bpmn' => $bpmn,
        ]);
    }
}
