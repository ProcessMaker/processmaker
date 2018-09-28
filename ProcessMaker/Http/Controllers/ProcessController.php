<?php

namespace ProcessMaker\Http\Controllers;
use ProcessMaker\Models\Process;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Api\ResourceRequestsTrait;

class ProcessController extends Controller
{
    use ResourceRequestsTrait;

    public function index()
    {
        $processes = Process::all();  //what will be in the database = Model
        return view('processes.index', ["processes"=>$processes]);
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
        $this->encodeRequestUuids($request, ['user_uuid']);
        $request->validate(Process::rules());
        $process = new Process();
        $process->fill($request->input());
        $process->user_uuid = \Auth::user()->uuid;
        $process->saveOrFail();
        return redirect('/processes');
    }
    public function show(Process $process) // show new process to UI
    {
        return view('processes.show', ["process"=>$process]);  // from data item in index, once clicked, this page will show with ability to edit and destroy
    }
    public function update(Process $process, Request $request) // update existing process to DB
    {
        $request->validate(Process::rules($script));
        $process->fill($request->input());
        $process->saveOrFail();
        return response([], 204);
    }
    // public function destroy() // destory existing process to DB / UI
    // {
    //     $process = Process::find($process->id);
    //     $process->delete();
    //     //return modal
    // }

}
