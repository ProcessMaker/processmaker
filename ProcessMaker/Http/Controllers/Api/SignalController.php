<?php

namespace ProcessMaker\Http\Controllers\Api;

use DOMXPath;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Managers\SignalManager;
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

        $signals = SignalManager::getAllSignals();

        $filter = $request->input('filter', '');
        if ($filter) {
            $signals = $signals->filter(function ($signal, $key) use($filter) {
                return mb_stripos($signal['name'], $filter) !== false
                        || mb_stripos($signal['id'], $filter) !== false;
            });
        }

        $orderBy = $request->input('order_by', 'id');
        $orderDirection = $request->input('order_direction', 'ASC');

        $signals = strcasecmp($orderDirection, 'DESC') === 0
                ? $signals->sortByDesc($orderBy)->values()
                : $signals->sortBy($orderBy)->values();

        $perPage = $request->input('per_page', 10);
        $lastPage = intval(floor($signals->count() / $perPage)) + 1;
        $page = $request->input('page', 1) > $lastPage
                ? $lastPage
                : $request->input('page', 1);

        $meta = [
            'count' => $signals->count(),
            'current_page' => $page,
            'filter' => $filter,
            'from' => $perPage * ($page - 1) + 1,
            'last_page' => $lastPage,
            'path' => '/',
            'per_page' => $perPage,
            'sort_by' => $orderBy,
            'sort_order' => strtolower($orderDirection),
            'to' => $perPage * ($page - 1) + $perPage,
            'total' => $signals->count(),
            'total_pages' => $lastPage
        ];

        $signals = $signals->count() === 0 ? [] : $signals->chunk($perPage)[$page - 1];

        return response()->json([
            'data' => $signals,
            'meta' => $meta
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  mixed  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response(SignalManager::getAllSignals()->firstWhere('id', $id), 200);
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

        $errorValidations = SignalManager::validateSignal($newSignal, null);
        if (count($errorValidations) > 0) {
            return response(['errors' => $errorValidations], 422);
        }

        SignalManager::addSignal($newSignal);

        return response(['id' => $newSignal->getId(), 'name' => $newSignal->getName()], 200);
    }

    public function update(Request $request, $signalId)
    {
        $newSignal = new Signal();
        $newSignal->setId($request->input('id'));
        $newSignal->setName($request->input('name'));

        $oldSignal = SignalManager::findSignal($signalId);

        $errorValidations = SignalManager::validateSignal($newSignal, $oldSignal);
        if (count($errorValidations) > 0) {
            return response(implode('; ', $errorValidations), 422);
        }

        SignalManager::replaceSignal($newSignal, $oldSignal);

        return response(['id' => $newSignal->getId(), 'name' => $newSignal->getName()], 200);
    }

    public function destroy($signalId)
    {
        $signal = SignalManager::findSignal($signalId);
        if ($signal) {
            SignalManager::removeSignal($signal);
        }
        return response('', 201);
    }
}
