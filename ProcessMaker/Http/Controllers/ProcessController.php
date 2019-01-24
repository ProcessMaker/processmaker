<?php

namespace ProcessMaker\Http\Controllers;

use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use Illuminate\Http\Request;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessPermission;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;

class ProcessController extends Controller
{
    /**
     * A blacklist of attributes that should not be
     * sanitized by our SanitizeInput middleware.
     *
     * @var array
     */
    public $doNotSanitize = [
        'bpmn',
    ];

    public function index(Request $request)
    {
        $status = $request->input('status');
        $processes = Process::all(); //what will be in the database = Model
        $processCategories = ProcessCategory::where('status', 'ACTIVE')->get();
        $processCategoryArray = ['' => 'None'];
        foreach ($processCategories as $pc) {
            $processCategoryArray[$pc->id] = $pc->name;
        }
        return view('processes.index',
            [
                "processes" => $processes,
                "processCategories" => $processCategoryArray,
                "status" => $status
            ]);
    }

    public function dashboard(){
        return view('processes.dashboard');
    }

    /**
     * @param Process $process
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Process $process)
    {
        $categories = ProcessCategory::orderBy('name')
            ->where('status', 'ACTIVE')
            ->get()
            ->pluck('name', 'id')
            ->toArray();
        
        $screens = Screen::orderBy('title')
            ->get()
            ->pluck('title', 'id')
            ->toArray();
        
        $list = $this->listUsersAndGroups();
        
        $canStart = $this->listCan('Start', $process);
        $canCancel = $this->listCan('Cancel', $process);

        return view('processes.edit', compact(['process', 'categories', 'screens', 'list', 'canCancel', 'canStart']));
    }

    /**
     * Listing users & groups that can edit a particular process
     *
     * @param $method
     * @param $process
     *
     * @return array Users|Groups
     */        
    private function listCan($method, Process $process)
    {
        $users = $process->{"usersCan$method"}()->select('id', 'firstname', 'lastname')->get();
        $groups = $process->{"groupsCan$method"}()->select('id', 'name as fullname')->get();

        $merge = collect([]);
        
        $users->map(function ($item) use ($merge) {
            $item->type = 'user';
            $merge->push($item);
        });
        
        $groups->map(function ($item) use ($merge) {
            $item->type = 'group';
            $merge->push($item);
        });
        
        return $merge;
    }
    
    /**
     * List active users and groups
     *
     * @return array Users|Groups
     */    
    private function listUsersAndGroups()
    {
        $users = User::active()->select('id', 'firstname', 'lastname')->get();
        $groups = Group::active()->select('id', 'name as fullname')->get();
        
        $users->map(function ($item) {
            $item->type = 'user';
            return $item;
        });
        
        $groups->map(function ($item) {
            $item->type = 'group';
            return $item;
        });
        
        return [
            [
                'label' => 'Users',
                'items' => $users,
            ],
            [
                'label' => 'Groups',
                'items' => $groups,            
            ],
        ];
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
