<?php

namespace ProcessMaker\Http\Controllers;
use ProcessMaker\Models\Process;
use Illuminate\Http\Request;

class ProcessController extends Controller
{
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

    // public function store(Process $process) // store new process to DB
    // {
    //     $name = $process->name;
    // }
    // public function show(Process $process) // show new process to UI
    // {
    //     return view('processes.show', ["process"=>$process]);  // from data item in index, once clicked, this page will show with ability to edit and destroy
    // }
    // public function update(Process $process) // update existing process to DB
    // {
        
    // }
    // public function destroy() // destory existing process to DB / UI
    // {

    // }

}
