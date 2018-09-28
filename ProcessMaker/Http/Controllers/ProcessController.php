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
    // show, create, 
    public function create() // create new process
    {

    }

    public function store() // store new process to DB
    {

    }
    public function show() // show new process to UI
    {

    }
    public function update() // update existing process to DB
    {

    }
    public function destroy() // destory existing process to DB / UI
    {

    }

}
