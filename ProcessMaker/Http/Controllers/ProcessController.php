<?php

namespace ProcessMaker\Http\Controllers;

use Cache;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use Illuminate\Http\Request;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use ProcessMaker\Traits\HasControllerAddons;

class ProcessController extends Controller
{
    use HasControllerAddons;

    /**
     * A whitelist of attributes that should not be
     * sanitized by our SanitizeInput middleware.
     *
     * @var array
     */
    public $doNotSanitize = [
        'bpmn',
    ];

    /**
     * Get the list of procesess
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $redirect = $this->checkAuth();
        if ($redirect !== false) {
            return redirect()->route($redirect);
        }

        $catConfig = (object)[
            'labels' => (object)[
                'countColumn' => __('# Processes'),
            ],
            'routes' => (object)[
                'itemsIndexWeb' => 'processes.index',
                'editCategoryWeb' => 'process-categories.edit',
                'categoryListApi' => 'api.process_categories.index',
            ],
            'countField' => 'processes_count',
            'apiListInclude' => 'processesCount',
            'permissions' => [
                'view'   => $request->user()->can('view-process-categories'),
                'create' => $request->user()->can('create-process-categories'),
                'edit'   => $request->user()->can('edit-process-categories'),
                'delete' => $request->user()->can('delete-process-categories'),
            ],
        ];

        $listConfig = (object)[
            'processes' => Process::all(),
            'countCategories' => ProcessCategory::where(['status' => 'ACTIVE', 'is_system' => false])->count(),
            'status' => $request->input('status')
        ];

        return view('processes.index', compact('listConfig', 'catConfig'));
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

        $screenCancel = Screen::find($process->cancel_screen_id);
        $screenRequestDetail = Screen::find($process->request_detail_screen_id);

        $list = $this->listUsersAndGroups();

        $process->append('notifications', 'task_notifications');

        $canStart = $this->listCan('Start', $process);
        $canCancel = $this->listCan('Cancel', $process);
        $canEditData = $this->listCan('EditData', $process);
        $addons = $this->getPluginAddons('edit', compact(['process']));

        return view('processes.edit', compact(['process', 'categories', 'screenRequestDetail', 'screenCancel', 'list', 'canCancel', 'canStart', 'canEditData', 'addons']));
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

    public function export(Process $process)
    {
        return view('processes.export', compact('process'));
    }

    public function import(Process $process)
    {
        return view('processes.import');
    }

    /**
     * Download the JSON definition of the process
     *
     * @param Process $process
     * @param string $key
     *
     * @return stream
     */
    public function download(Process $process, $key)
    {
        $fileName = trim($process->name) . '.json';
        $fileContents = Cache::get($key);

        if (!$fileContents) {
            return abort(404);
        } else {
            return response()->streamDownload(function () use ($fileContents) {
                echo $fileContents;
            }, $fileName, [
                'Content-type' => 'application/json',
            ]);
        }
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

    private function checkAuth()
    {
        $perm = 'view-processes|view-process-categories|view-scripts|view-screens|view-environment_variables';
        switch (\Auth::user()->canAny($perm)) {
            case 'view-processes':
                return false; // already on index, continue with it
            case 'view-process-categories':
                return 'process-categories.index';
            case 'view-scripts':
                return 'scripts.index';
            case 'view-screens':
                return 'screens.index';
            case 'view-environment_variables':
                return 'environment-variables.index';
            default:
                throw new AuthorizationException();
        }
    }
}
