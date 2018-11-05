<?php

namespace ProcessMaker\Http\Controllers;
use ProcessMaker\Models\Process;
use Illuminate\Http\Request;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;

class ProcessController extends Controller
{
    public function index()
    {
        $processes = Process::all(); //what will be in the database = Model
        $processCategories = ProcessCategory::all();
        $processCategoryArray = ['' => 'None'];
        foreach($processCategories as $pc){
            $processCategoryArray[$pc->id] = $pc->name;
        }
        return view('processes.index', ["processes"=>$processes, "processCategories"=>$processCategoryArray]);
    }
    public function edit(Process $process)
    {
        $categories = ProcessCategory::where('status', 'ACTIVE')
                        ->orderBy('name')
                        ->get()
                        ->pluck('name', 'id');

        $screens = Screen::orderBy('title')
                    ->get()
                    ->pluck('title', 'id');

        return view('processes.edit', compact('process', 'categories', 'screens'));
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
        // Redirect to our modeler
        return redirect()->to(route('modeler'));
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
