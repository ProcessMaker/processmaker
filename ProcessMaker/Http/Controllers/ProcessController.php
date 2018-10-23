<?php

namespace ProcessMaker\Http\Controllers;
use ProcessMaker\Models\Process;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Api\ResourceRequestsTrait;
use ProcessMaker\Models\ProcessCategory;

class ProcessController extends Controller
{
    public function index()
    {
        $processes = Process::all(); //what will be in the database = Model
        $processCategories = ProcessCategory::all();
        $processCategoryArray = [];
        foreach($processCategories as $pc){
            $processCategoryArray[$pc->uuid_text] = $pc->name;
        }
        return view('processes.index', ["processes"=>$processes, "processCategories"=>$processCategoryArray]);
    }
    public function edit(Process $process)
    {
        return view('processes.edit', ["process"=>$process]);
    }

    public function create() // create new process
    {
        return view('processes.create');
    }

    public function store(Request $request) // store new process to DB
    {
        $request->validate(Process::rules());
        $process = new Process();
        $process->fill($request->input());
        $process->user_id = \Auth::user()->getKey();
        $process->bpmn = '';
        $process->saveOrFail();
        return redirect('/processes');
    }
    public function show(Process $process) // show new process to UI
    {
        return view('processes.show', ["process"=>$process]);  // from data item in index, once clicked, this page will show with ability to edit and destroy
    }
    public function update(Process $process, Request $request) // update existing process to DB
    {
        $request->validate(Process::rules($request));
        $process->fill($request->input());
        $process->saveOrFail();
        return redirect('/processes');
    }
    public function destroy(Process $process) // destory existing process to DB / UI
    {
        $process->delete();
        return redirect('/processes');
    }

}
