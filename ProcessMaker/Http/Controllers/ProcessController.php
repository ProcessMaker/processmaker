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
}
