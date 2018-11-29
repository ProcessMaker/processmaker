<?php

namespace ProcessMaker\Http\Controllers;

use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\PermissionAssignment;
use ProcessMaker\Models\Process;
use Illuminate\Http\Request;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessPermission;
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

    /**
     * @param Process $process
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
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

        //list users and groups with permissions requests.cancel
        $listCancel = [
            'Users' => $this->assignee('requests.cancel', User::class),
            'Groups' => $this->assignee('requests.cancel', Group::class)
        ];

        //list users and groups with permission requests.create
        $listStart = [
            'Users' => $this->assignee('requests.create', User::class),
            'Groups' => $this->assignee('requests.create', Group::class)
        ];
        $process->cancel_request_id = $this->loadAssigneeProcess('requests.cancel',  $process->id);
        $process->start_request_id = $this->loadAssigneeProcess('requests.create',  $process->id);

        return view('processes.edit', compact(['process', 'categories', 'screens', 'listCancel', 'listStart']));
    }

    /**
     * Load users or groups assigned with the permission
     *
     * @param $permission
     * @param $type
     *
     * @return array Users|Groups assigned
     */
    private function assignee($permission, $type)
    {
        $items = PermissionAssignment::where('permission_id', Permission::byGuardName($permission)->id)
            ->where('assignable_type', $type)
            ->get();
        $data = [];
        foreach ($items as $assigned) {
            $item = $type::find($assigned->assignable_id);
            $data[($item->fullname ? 'user-' : 'group-') . $item->id] = $item->fullname ?: $item->name;
        }
        return $data;
    }

    /**
     * Load users and groups assigned to process
     *
     * @param $permission
     * @param $processId
     *
     * @return string|null
     */
    private function loadAssigneeProcess($permission, $processId)
    {
        $assignee = ProcessPermission::where('permission_id', Permission::byGuardName($permission)->id)
            ->where('process_id', $processId)
            ->first();
        if ($assignee) {
            $assignee = ($assignee->assignable_type === User::class ? 'user-' : 'group-') . $assignee->assignable_id;
        }
        return $assignee;
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
