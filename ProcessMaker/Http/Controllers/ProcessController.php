<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Http\Controllers\Api\ProcessController as ApiProcessController;
use ProcessMaker\Jobs\ImportV2;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
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

        $catConfig = (object) [
            'labels' => (object) [
                'countColumn' => __('# Processes'),
            ],
            'routes' => (object) [
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

        $listConfig = (object) [
            'countCategories' => ProcessCategory::where(['status' => 'ACTIVE', 'is_system' => false])->count(),
            'status' => $request->input('status'),
        ];

        $listTemplates = (object) [
            'permissions' => [
                'view'   => $request->user()->can('view-process-templates'),
                'create' => $request->user()->can('create-process-templates'),
                'edit'   => $request->user()->can('edit-process-templates'),
                'delete' => $request->user()->can('delete-process-templates'),
                'import' => $request->user()->can('import-process-templates'),
                'export' => $request->user()->can('export-process-templates'),
            ],
        ];

        return view('processes.index', compact('listConfig', 'catConfig', 'listTemplates'));
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
        $assignedProjects = json_decode($process->projects, true);

        $canStart = $this->listCan('Start', $process);
        $canCancel = $this->listCan('Cancel', $process);
        $canEditData = $this->listCan('EditData', $process);
        $addons = $this->getPluginAddons('edit', compact(['process']));

        $lastDraftOrPublishedVersion = $process->getDraftOrPublishedLatestVersion();

        $isDraft = 0;
        if ($lastDraftOrPublishedVersion) {
            $isDraft = $lastDraftOrPublishedVersion->draft;
        }

        return view('processes.edit', compact(['process', 'categories', 'screenRequestDetail', 'screenCancel', 'list', 'canCancel', 'canStart', 'canEditData', 'addons', 'assignedProjects', 'isDraft']));
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
        $users = User::nonSystem()->active()->select('id', 'firstname', 'lastname')->get();
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
                'label' => __('Users'),
                'items' => $users,
            ],
            [
                'label' => __('Groups'),
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

    public function export(Request $request, Process $process)
    {
        $projectId = $request->query('project_id');

        return view('processes.export', compact('process', 'projectId'));
    }

    public function import(Request $request, Process $process)
    {
        if ($request->get('forceUnlock')) {
            $result = Cache::lock(ImportV2::CACHE_LOCK_KEY)->forceRelease();
            Session::flash('_alert', json_encode(['success', 'unlocked ' . $result]));

            return redirect()->route('processes.import');
        }
        $importIsRunning = ImportV2::isRunning();

        return view('processes.import', compact('importIsRunning'));
    }

    public function downloadImportDebug(Request $request)
    {
        $hash = $request->get('hash');
        if ($hash !== md5_file(Storage::path(ImportV2::FILE_PATH))) {
            throw new \Exception('File hash does not match');
        }

        $zip = new \ZipArchive();
        $debugFilePath = Storage::path(ImportV2::DEBUG_ZIP_PATH);
        if (true === ($zip->open($debugFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE))) {
            $zip->addFile(Storage::path(ImportV2::FILE_PATH), 'payload.json');
            $zip->addFile(Storage::path(ImportV2::MANIFEST_PATH), 'manifest.json');
            $zip->addFile(Storage::path(ImportV2::OPTIONS_PATH), 'options.json');
            $zip->addFile(Storage::path(ImportV2::LOG_PATH), 'log.txt');
            $zip->close();
        }

        return response()->download($debugFilePath);
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

    public function triggerStartEventApi(Process $process, Request $request)
    {
        $apiRequest = new ApiProcessController();
        $response = $apiRequest->triggerStartEvent($process, $request);

        return redirect('/requests/' . $response->id . '?fromTriggerStartEvent=');
    }

    private function checkAuth()
    {
        $perm = 'view-processes|view-process-categories|view-scripts|view-screens|view-environment_variables';
        switch (\Auth::user()->canAnyFirst($perm)) {
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
