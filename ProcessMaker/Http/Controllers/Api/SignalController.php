<?php

namespace ProcessMaker\Http\Controllers\Api;

use DOMXPath;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Models\Process;
use ProcessMaker\Nayra\Bpmn\Models\Signal;
use ProcessMaker\Query\SyntaxError;
use ProcessMaker\Repositories\BpmnDocument;

class SignalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Process::query()->orderBy('updated_at', 'desc');
        $pmql = $request->input('pmql', '');
        if (!empty($pmql)) {
            try {
                $query->pmql($pmql);
            } catch (SyntaxError $e) {
                return response(['message' => __('Your PMQL contains invalid syntax.')], 400);
            }
        }

        $signals = $this->getAllSignals();

        $filter = $request->input('filter', '');
        if ($filter) {
            $signals = array_values(array_filter($signals, function ($signal) use ($filter) {
                return mb_stripos($signal['name'], $filter) !== false;
            }));
        }
        return response()->json(['data' => $signals]);
    }

    /**
     * Display the specified resource.
     *
     * @param  mixed  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $processes = Process::query()->orderBy('updated_at', 'desc')->get();

        $signals = [];
        foreach ($processes as $process) {
            $document = $process->getDomDocument();
            $nodes = $document->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'signal');
            foreach ($nodes as $node) {
                if ($id === $node->getAttribute('id')) {
                    $signals = [
                        'id' => $node->getAttribute('id'),
                        'name' => $node->getAttribute('name'),
                    ];
                    break;
                }
            }
        }

        return response($signals);
    }

    /**
     * Creates a new global signal
     *
     * @param Request $request
     */
    public function store(Request $request)
    {
        $newSignal = new Signal();
        $newSignal->setId($request->input('id'));
        $newSignal->setName($request->input('name'));

        $errorValidations = $this->validateSignal($newSignal, null);
        if (count($errorValidations) > 0) {
            return response(implode('; ', $errorValidations), 422);
        }

        $this->addSignal($newSignal);

        return response(['id' => $newSignal->getId(), 'name' => $newSignal->getName()], 200);
    }

    public function update(Request $request, $signalId)
    {
        $newSignal = new Signal();
        $newSignal->setId($request->input('id'));
        $newSignal->setName($request->input('name'));

        $oldSignal = $this->findSignal($signalId);

        $errorValidations = $this->validateSignal($newSignal, $oldSignal);
        if (count($errorValidations) > 0) {
            return response(implode('; ', $errorValidations), 422);
        }

        $this->replaceSignal($newSignal, $oldSignal);

        return response(['id' => $newSignal->getId(), 'name' => $newSignal->getName()], 200);
    }

    private function addSignal(Signal $signal)
    {
        $signalProcess = $this->getGlobalSignalProcess();
        $definitions = $signalProcess->getDefinitions();
        $newnode = $definitions->createElementNS(BpmnDocument::BPMN_MODEL, "bpmn:signal");
        $newnode->setAttribute('id', $signal->getId());
        $newnode->setAttribute('name', $signal->getName());
        $definitions->firstChild->appendChild($newnode);
        $signalProcess->bpmn = $definitions->saveXML();
        $signalProcess->save();
    }

    private function replaceSignal(Signal $newSignal, Signal $oldSignal)
    {
        $signalProcess = $this->getGlobalSignalProcess();
        $definitions = $signalProcess->getDefinitions();
        $newNode = $definitions->createElementNS(BpmnDocument::BPMN_MODEL, "bpmn:signal");
        $newNode->setAttribute('id', $newSignal->getId());
        $newNode->setAttribute('name', $newSignal->getName());

        $x = new DOMXPath($definitions);
        if ($x->query("//*[@id='" . $oldSignal->getId() . "']")->count() > 0 ) {
            $oldNode = $x->query("//*[@id='" . $oldSignal->getId() . "']")->item(0);
            $definitions->firstChild->replaceChild($newNode, $oldNode);
            $signalProcess->bpmn = $definitions->saveXML();
            $signalProcess->save();
        }
    }

    /**
     * @return Process
     */
    private function getGlobalSignalProcess()
    {
        // TODO use a system process created when seeding
        return Process::find(33);
    }

    /**
     * @param Signal $newSignal
     * @param Signal $oldSignal In case of an insert, this variable is null
     *
     * @return array
     */
    private function validateSignal(Signal $newSignal, Signal $oldSignal)
    {
        $result = [];

        if ( !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $newSignal->getId()) ) {
            $result[] = 'The signal ID should be an alphanumeric string';
        }

        $signalIdExists =  count(
            array_filter($this->getAllSignals(), function($sig) use($newSignal, $oldSignal) {
                return $sig['id'] === $newSignal->getId()
                        && (empty($oldSignal) ? true : $sig['id'] !== $oldSignal->getId());
            })
        ) > 0;

        if ($signalIdExists) {
            $result[] = 'The signal ID already exists';
        }

        $signalNameExists =  count(
                array_filter($this->getAllSignals(), function($sig) use($newSignal, $oldSignal) {
                    return $sig['name'] === $newSignal->getName()
                            && (empty($oldSignal) ? true : $sig['name'] !== $oldSignal->getName());
                })
            ) > 0;

        if ($signalNameExists) {
            $result[] = 'The signal name already exists';
        }

        return $result;
    }

    private function getAllSignals()
    {
        $signals = [];
        foreach (Process::all() as $process) {
            $document = $process->getDomDocument();
            $nodes = $document->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'signal');
            foreach ($nodes as $node) {
                $filter = array_filter($signals, function ($signal) use ($node) {
                    return $signal['id'] === $node->getAttribute('id');
                });
                if (count($filter) === 0) {
                    $signals[] = [
                        'id' => $node->getAttribute('id'),
                        'name' => $node->getAttribute('name'),
                    ];
                }
            }
        }
        usort($signals, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        return $signals;
    }

    /**
     * @param $signalId
     *
     * @return Signal | null
     */
    private function findSignal($signalId)
    {
        $signals = array_filter($this->getAllSignals(), function ($sig) use ($signalId) {
            return $sig['id'] === $signalId;
        });

        $result = null;
        if(count($signals) > 0) {
          $signal = array_pop($signals) ;
          $result = new Signal();
          $result->setId($signal['id']);
          $result->setName($signal['name']);
        }

        return $result;
    }
}
