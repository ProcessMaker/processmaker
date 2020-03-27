<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Process;
use ProcessMaker\Repositories\BpmnDocument;

class SignalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //$until = now()->addMinutes(1);
        //$signals = Cache::remember('signals', $until, function () {
            $signals = [];
            $processes = Process::all();
            foreach($processes as $process) {
                $document = $process->getDomDocument();
                $nodes = $document->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'signal');
                foreach($nodes as $node) {
                    $signals[] = [
                        'id' => $node->getAttribute('id'),
                        'name' => $node->getAttribute('name'),
                    ];
                }
            }
            //return $signals;
        //});
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
