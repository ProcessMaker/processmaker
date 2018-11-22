<?php

namespace ProcessMaker\Http\Controllers;

use ProcessMaker\Http\Resources\Groups;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\PermissionAssignment;
use ProcessMaker\Models\Process;
use Illuminate\Http\Request;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;

class ProcessController extends Controller
{
    public function index()
    {
        $processes = Process::all(); //what will be in the database = Model
        $processCategories = ProcessCategory::all();
        $processCategoryArray = ['' => 'None'];
        foreach ($processCategories as $pc) {
            $processCategoryArray[$pc->id] = $pc->name;
        }
        return view('processes.index', ["processes" => $processes, "processCategories" => $processCategoryArray]);
    }

    public function edit(Process $process)
    {
        $categories = ProcessCategory::orderBy('name')
            ->get()
            ->pluck('name', 'id')
            ->toArray();

        $screens = Screen::orderBy('title')
            ->get()
            ->pluck('title', 'id')
            ->toArray();

        //list users and group with permissions processes.cancel
        $assignments = [
            'Users' => [],
            'Groups' => [],
        ];

        $groups = PermissionAssignment::where('permission_id', Permission::byGuardName('processes.cancel')->id)
            ->where('assignable_type', Group::class)
            ->get();

        $users = PermissionAssignment::where('permission_id', Permission::byGuardName('processes.cancel')->id)
            ->where('assignable_type', User::class)
            ->get();

        foreach ($groups as $assigned) {
            $group = Group::find($assigned->assignable_id);
            $assignments['Groups'][$group->id] = $group->name;
        }
        foreach ($users as $assigned) {
            $user = User::find($assigned->assignable_id);
            $assignments['Users'][$user->id] = $user->fullname;
        }

        return view('processes.edit', compact('process', 'categories', 'screens', 'assignments'));
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
