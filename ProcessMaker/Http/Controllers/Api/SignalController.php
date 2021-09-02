<?php

namespace ProcessMaker\Http\Controllers\Api;

use DOMXPath;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\SignalData;
use ProcessMaker\Query\SyntaxError;

class SignalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return Response
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

        $signals = SignalManager::getAllSignals(false, $query->get()->all());

        $collections = [];
        $collectionsEnabled = [];

        if(hasPackage('package-collections')) {
            $collection = \ProcessMaker\Plugins\Collections\Models\Collection::get();
            
            foreach ($collection as $item) {
                $collectionsEnabled[] = $item->id;
                if (!$item->signal_create) {
                    $collections[] = 'collection_' . $item->id . '_create';
                }
                if (!$item->signal_update) {
                    $collections[] = 'collection_' . $item->id . '_update';
                }
                if (!$item->signal_delete) {
                    $collections[] = 'collection_' . $item->id . '_delete';
                }
            };
        }

        //verify active signals
        $replace = ['collection_', '_create', '_update', '_delete'];
        $signals = $signals->transform(function($item) use($collections, $collectionsEnabled, $replace) {
            if (!in_array($item['id'], $collections)) {
                $item['type'] = 'signal';

                if (preg_match('/\bcollection_[0-9]_(create|update|delete)\b/', $item['id']) && in_array(str_replace($replace, '', $item['id']), $collectionsEnabled)) {
                    $item['type'] = 'collection';
                }
                return $item;
            }
        });

        //remove items nulls
        $signals = $signals->filter();

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
        $page = (int)$page;

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

        $signals = $signals->count() === 0 ? $signals : $signals->chunk($perPage)[$page - 1];
        $meta['count'] = $signals->count();

        return response()->json([
            'data' => $signals,
            'meta' => $meta
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  mixed  $id
     * @return Response
     */
    public function show($id)
    {
        return response(SignalManager::getAllSignals()->firstWhere('id', $id), 200);
    }

    /**
     * Creates a new global signal
     *
     * @param Request $request
     *
     * @return Application|ResponseFactory|Response
     */
    public function store(Request $request)
    {
        $newSignal = new SignalData(
            $request->input('id', ''),
            $request->input('name', ''),
            $request->input('detail', '')
        );

        $errorValidations = SignalManager::validateSignal($newSignal, null);
        if (count($errorValidations) > 0) {
            return response(['errors' => $errorValidations], 422);
        }

        SignalManager::addSignal($newSignal, ['detail' => $request->input('detail', '')]);

        return response(['id' => $newSignal->getId(), 'name' => $newSignal->getName()], 200);
    }

    public function update(Request $request, $signalId)
    {
        $newSignal = new SignalData(
            $request->input('id'),
            $request->input('name'),
            $request->input('detail', '')
        );

        $oldSignal = SignalManager::findSignal($signalId);

        $errorValidations = SignalManager::validateSignal($newSignal, $oldSignal);
        if (count($errorValidations) > 0) {
            return response(["errors" => $errorValidations], 422);
        }

        SignalManager::replaceSignal($newSignal, $oldSignal, ['detail' => $request->input('detail', '')]);

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
