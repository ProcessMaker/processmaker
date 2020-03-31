<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Process;
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
        $processes = $query->get();
        $signals = [];
        foreach($processes as $process) {
            $document = $process->getDomDocument();
            $nodes = $document->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'signal');
            foreach($nodes as $node) {
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
        $filter = $request->input('filter', '');
        if ($filter) {
            $signals = array_values(array_filter($signals, function ($signal) use($filter) {
                return mb_stripos($signal['name'], $filter) !== false;
            }));
        }
        return response()->json(['data' => $signals]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }
}
