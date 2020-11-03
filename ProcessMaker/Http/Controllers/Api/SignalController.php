<?php

namespace ProcessMaker\Http\Controllers\Api;

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
        $signal = new Signal();
        $signal->setId($request->input('id'));
        $signal->setName($request->input('name'));

        $errorValidations = $this->validateNewSignal($signal);
        if (count($errorValidations) > 0) {
            return response(implode('; ', $errorValidations), 422);
        }

        $this->addSignal($signal);

        return response(['id' => $signal->getId(), 'name' => $signal->getName()], 200);
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

    /**
     * @return Process
     */
    private function getGlobalSignalProcess()
    {
        // TODO use a system process created when seeding
        return Process::find(33);
    }

    /**
     * @param Signal $signal
     *
     * @return array
     */
    private function validateNewSignal(Signal $signal)
    {
        $result = [];

        if ( !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $signal->getId()) ) {
            $result[] = 'The signal ID should be an alphanumeric string';
        }

        $signalIdExists =  count(
            array_filter($this->getAllSignals(), function($sig) use($signal) {
                return $sig['id'] === $signal->getId();
            })
        ) > 0;

        if ($signalIdExists) {
            $result[] = 'The signal ID already exists';
        }

        $signalNameExists =  count(
                array_filter($this->getAllSignals(), function($sig) use($signal) {
                    return $sig['name'] === $signal->getName();
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
}
