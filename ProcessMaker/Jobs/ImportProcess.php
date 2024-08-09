<?php

namespace ProcessMaker\Jobs;

use Auth;
use Carbon\Carbon;
use DB;
use DOMXPath;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Events\ImportedScreenSaved;
use ProcessMaker\Managers\ExportManager;
use ProcessMaker\Models\AnonymousUser;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessNotificationSetting;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\ImportReady;
use ProcessMaker\Package\WebEntry\Models\WebentryRoute;
use ProcessMaker\Providers\WorkflowServiceProvider;
use ProcessMaker\Traits\PluginServiceProviderTrait;

class ImportProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, PluginServiceProviderTrait;

    /**
     * The original contents of the imported file.
     *
     * @var string
     */
    private $fileContents;

    /**
     * Importing code.
     *
     * @var string
     */
    private $code;

    /**
     * The path of the imported file.
     *
     * @var string
     */
    private $path;

    /**
     * The decoded object obtained from the file.
     *
     * @var object
     */
    protected $file;

    /**
     * The process being imported.
     *
     * @var object
     */
    private $process;

    /**
     * The BPMN definitions of the process.
     *
     * @var object
     */
    private $definitions;

    private $assignable = [];

    /**
     * An array of the new models being created on import.
     *
     * @var object[]
     */
    protected $new = [];

    /**
     * An array with the state completed, incomplete or failure of the elements to be imported
     *
     * @var array
     */
    protected $status = [];

    /**
     * User that requests the import
     *
     * @var int
     */
    protected $user;

    /**
     * Prevent duplicate processes from being imported if this job fails
     *
     * @var int
     */
    public $tries = 1;

    /**
     * In order to handle backwards compatibility with previous packages, an
     * array with a previous package name as the key, and the updated
     * package name as the value.
     *
     * @var array
     */
    private $backwardCompatiblePackageMap = [
        'processmaker-communication-email-send' => 'processmaker-connector-send-email',
    ];

    public $newProcessId = null;

    /**
     * Create a new job instance and set the file contents.
     *
     * @return void
     */
    public function __construct($fileContents, $code = false, $path = null, $user = null)
    {
        $this->fileContents = $fileContents;
        $this->code = $code;
        $this->path = $path;
        $this->user = $user;
    }

    /**
     * Return a valid method name based on the version of the imported file,
     * or return false if no such method exists.
     *
     * @return string|bool
     */
    protected function getParser()
    {
        $method = "parseFileV{$this->file->version}";
        if (method_exists($this, $method)) {
            return $method;
        } else {
            return false;
        }
    }

    /**
     * Return either the currently authorized user or, if this happens to be
     * run in the console, the first user in the database.
     *
     * @return object
     */
    private function currentUser()
    {
        if (!app()->runningInConsole()) {
            return Auth::user();
        } else {
            return User::first();
        }
    }

    /**
     * Pass a date in string format to be parsed and returned as either a
     * Carbon object, or set to null if null.
     *
     * @param string|null $date
     *
     * @return resource|null
     */
    protected function formatDate($date)
    {
        if ($date) {
            return new Carbon($date);
        } else {
            return null;
        }
    }

    /**
     * Pass the name, field name, and class of an object, then check the
     * database for duplicate names. If there are duplicates, append
     * an incremental number to the name.
     *
     * @param string $name
     * @param string $field
     * @param object $class
     *
     * @return string
     */
    protected function formatName($name, $field, $class)
    {
        //Create a new instance of this model
        $model = new $class;

        //Find duplicates of this item's name
        $dupe = $model->where($field, 'like', $name . '%')
            ->orderBy(DB::raw("LENGTH($field), $field"))
            ->get();

        //If we have duplicates...
        if ($dupe->count()) {
            //Get the last duplicate
            $dupe = $dupe->last();

            //Match the number at the end of the name
            $doesMatch = preg_match('/(\d+)$/', $dupe->{$field}, $matches);

            //If we have a match...
            if ($doesMatch === 1) {
                //Increment the number!
                $number = intval($matches[1]) + 1;
            } else {
                //Start with 2
                $number = 2;
            }

            //the name appended with the number
            $name = $name . ' ' . $number;

            //verify existence of the new name
            if ($model->where($field, $name)->exists()) {
                return $this->formatName($name, $field, $class);
            } else {
                //Return the new name
                return $name;
            }
        } else {
            //Return the original name (if there are no dupes)
            return $name;
        }
    }

    private function getElementById($id)
    {
        $x = new DOMXPath($this->definitions);

        return $x->query("//*[@id='$id']")->item(0);
    }

    /**
     * Look for any assignable entities in the BPMN, then add them to a list.
     *
     * @return void
     */
    private function parseAssignables()
    {
        $this->assignable = collect([]);

        $this->parseAssignableStartEvent();
        $this->parseWebEntryCustomRoutes();
        $this->parseAssignableTasks();
        $this->parseAssignableCallActivity();
        $this->parseAssignableScripts();
        $this->parseAssignableWatchers();

        if (!$this->assignable->count()) {
            $this->assignable = null;
        }
    }

    /**
     * Look for any assignable Start event and add them to the assignable list.
     *
     * @return void
     */
    private function parseAssignableStartEvent()
    {
        $tasks = $this->definitions->getElementsByTagName('startEvent');
        foreach ($tasks as $task) {
            $assignment = $task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignment');
            $eventDefinition = $task->getElementsByTagName('timerEventDefinition');
            if (!$assignment && $eventDefinition->count() === 0) {
                $this->assignable->push((object) [
                    'type' => 'startEvent',
                    'id' => $task->getAttribute('id'),
                    'name' => $task->getAttribute('name'),
                    'prefix' => __('Assign Start Event'),
                    'suffix' => __('to'),
                ]);
            }
        }
    }

    private function parseWebEntryCustomRoutes()
    {
        $tasks = $this->definitions->getElementsByTagName('startEvent');
        $importedProcessId = $this->newProcessId;
        foreach ($tasks as $task) {
            $config = json_decode($task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'config'), true);
            if (isset($config['web_entry']) && isset($config['web_entry']['webentryRouteConfig'])) {
                $webEntryRouteConfig = $config['web_entry']['webentryRouteConfig'];
                if ($webEntryRouteConfig['firstUrlSegment'] != '') {
                    $error = null;
                    $existingRoute = WebentryRoute::where('first_segment', $webEntryRouteConfig['firstUrlSegment'])->where('process_id', '!=', $importedProcessId)->first();
                    if ($existingRoute) {
                        $existingProcess = Process::select('name')->where('id', $existingRoute->process_id)->first();
                        $error = __('Route should be unique. Used in process: :process_name in node ID: :node_id', ['process_name' => $existingProcess->name, 'node_id' => $existingRoute->node_id]);
                    }

                    $this->assignable->push((object) [
                        'type' => 'webentryCustomRoute',
                        'id' => $task->getAttribute('id'),
                        'name' => $task->getAttribute('name'),
                        'prefix' => __('Assign'),
                        'suffix' => __('Custom Web Entry Route to'),
                        'error' => $error,
                        'value' => $webEntryRouteConfig['firstUrlSegment'],
                    ]);
                }
            }
        }
    }

    /**
     * Look for any assignable Call Activity and add them to the assignable list.
     *
     * @return void
     */
    private function parseAssignableCallActivity()
    {
        $tasks = $this->definitions->getElementsByTagName('callActivity');
        foreach ($tasks as $task) {
            $assignment = $task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'calledElement');
            // Do not ask to assign a target if this CallActivity is a package wrapper
            if ($this->isCallActivityFromPackage($task)) {
                continue;
            }

            if (!$assignment) {
                $this->assignable->push((object) [
                    'type' => 'callActivity',
                    'id' => $task->getAttribute('id'),
                    'name' => $task->getAttribute('name'),
                    'prefix' => __('Assign Call Activity'),
                    'suffix' => __('to'),
                ]);
            }
        }
    }

    /**
     * Look for any assignable tasks and add them to the assignable list.
     *
     * @return void
     */
    private function parseAssignableTasks()
    {
        //tasks that should always be assigned
        $humanTasks = ['task', 'userTask', 'manualTask'];
        foreach ($humanTasks as $humanTask) {
            $tasks = $this->definitions->getElementsByTagName($humanTask);
            foreach ($tasks as $task) {
                $assignment = $task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignment');
                // If an assignment rule is already set, do not ask to set it
                if ($assignment) {
                    continue;
                }

                $this->assignable->push((object) [
                    'type' => 'task',
                    'id' => $task->getAttribute('id'),
                    'name' => $task->getAttribute('name'),
                    'prefix' => __('Assign task'),
                    'suffix' => __('to'),
                ]);
            }
        }
    }

    /**
     * Look for any scripts and add them to the assignable list.
     *
     * @return void
     */
    private function parseAssignableScripts()
    {
        foreach ($this->new[Script::class] as $script) {
            $this->assignable->push((object) [
                'type' => 'script',
                'id' => $script->id,
                'name' => $script->title,
                'prefix' => __('Run script'),
                'suffix' => __('as'),
            ]);
        }
    }

    /**
     * Look for any watchers in screens and add them to the assignable list.
     *
     * @return void
     */
    private function parseAssignableWatchers()
    {
        foreach ($this->new[Screen::class] as $screen) {
            if (empty($screen->watchers)) {
                continue;
            }

            foreach ($screen->watchers as $index => $watcher) {
                $refParts = explode('-', $watcher['script']['id']);
                if ($refParts[0] !== 'data_source') {
                    continue;
                }
                $this->assignable[] = (object) [
                    'type' => 'watcherDataSource',
                    'id' => strval($screen->id) . '|' . strval($index),
                    'name' => $watcher['name'],
                    'prefix' => __('Assign data source watcher in :screen', ['screen' => $screen->title]),
                    'suffix' => __('to'),
                ];
            }
        }
    }

    /**
     * Parse the BPMN, looking for any task assignments to users and/or groups,
     * then remove them along with any referenced users or groups.
     *
     * @return void
     */
    private function removeAssignedEntities()
    {
        $humanTasks = ['task', 'userTask', 'manualTask'];
        foreach ($humanTasks as $humanTask) {
            $tasks = $this->definitions->getElementsByTagName($humanTask);
            foreach ($tasks as $task) {
                $assignment = $task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignment');
                if ($assignment == 'user' || $assignment == 'group') {
                    $task->removeAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignment');
                    $task->removeAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignedUsers');
                    $task->removeAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignedGroups');
                }
            }
        }
    }

    /**
     * Complete the process of updating screen refs.
     *
     * @return void
     */
    private function completeScreenRefs()
    {
        $humanTasks = ['task', 'userTask', 'endEvent', 'manualTask'];
        foreach ($humanTasks as $humanTask) {
            $tasks = $this->definitions->getElementsByTagName($humanTask);
            foreach ($tasks as $task) {
                $newScreenRef = $task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'screenRefNew');
                $task->removeAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'screenRefNew');
                $task->setAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'screenRef', $newScreenRef);
            }
        }
    }

    /**
     * Create a new Screen model for each screen object in the imported file,
     * then save it to the database.
     *
     * @param object[] $screens
     * @param Process $process
     *
     * @return void
     */
    private function saveScreens($screens, $process)
    {
        try {
            $this->new[Screen::class] = [];
            $this->prepareStatus('screens', count($screens) > 0);
            foreach ($screens as $screen) {
                $new = $this->saveScreen($screen);
                $this->new[Screen::class][$screen->id] = $new;
            }
            $this->finishStatus('screens');
        } catch (\Exception $e) {
            Log::info('*** Error: ' . $e->getMessage());
            $this->finishStatus('screens', true);
        }
    }

    /**
     * Create a new Screen model for an individual screen, then save it.
     *
     * @param Screen $screen
     *
     * @return void
     */
    protected function saveScreen($screen)
    {
        try {
            $new = new Screen;
            $new->computed = $screen->computed;
            $new->config = $screen->config;
            $new->created_at = $this->formatDate($screen->created_at);
            $new->custom_css = $screen->custom_css;
            $new->description = $screen->description;
            $new->title = $this->formatName($screen->title, 'title', Screen::class);
            $new->type = $screen->type;
            $new->watchers = $this->watcherScriptsToSave($screen);
            $new->save();
            event(new ImportedScreenSaved($new->id, $screen));

            // save categories
            if (isset($screen->categories)) {
                $ids = [];
                foreach ($screen->categories as $categoryDef) {
                    $category = $this->saveCategory('screen', $categoryDef);
                    if ($category) {
                        $ids[] = $category->id;
                    }
                }
                if (!empty($ids)) {
                    $new->screen_category_id = implode(',', $ids);
                }
                $new->save();
            }

            return $new;
        } catch (\Exception $e) {
            Log::error('Import Screen: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Pass an old script ID and a new script ID, then replace any references
     * within the BPMN to the old ID with the new ID.
     *
     * @param string|int $oldId
     * @param string|int $newId
     *
     * @return void
     */
    private function updateScriptRefs($oldId, $newId)
    {
        $tasks = $this->definitions->getElementsByTagName('scriptTask');
        foreach ($tasks as $task) {
            $scriptRef = $task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'scriptRef');
            if ($scriptRef == $oldId) {
                $task->setAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'scriptRefNew', $newId);
            }
        }
    }

    /**
     * Complete the process of updating script refs.
     *
     * @return void
     */
    private function completeScriptRefs()
    {
        $tasks = $this->definitions->getElementsByTagName('scriptTask');
        foreach ($tasks as $task) {
            $newScriptRef = $task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'scriptRefNew');
            $task->removeAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'scriptRefNew');
            $task->setAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'scriptRef', $newScriptRef);
        }
    }

    /**
     * Create a new Script model for each script object in the imported file,
     * then save it to the database.
     *
     * @param object[] $scripts
     *
     * @return void
     */
    private function saveScripts($scripts)
    {
        try {
            $this->new[Script::class] = [];
            $this->prepareStatus('scripts', count($scripts) > 0);
            foreach ($scripts as $script) {
                $new = $this->saveScript($script);
                $this->new[Script::class][$script->id] = $new;
            }
            $this->finishStatus('scripts');
        } catch (\Exception $e) {
            $this->finishStatus('scripts', true);
        }
    }

    /**
     * Create a new Script model for an individual screen, then save it.
     *
     * @param object[] $scripts
     *
     * @return Script
     */
    public function saveScript($script)
    {
        try {
            $new = new Script;
            $new->title = $this->formatName($script->title, 'title', Script::class);
            $new->description = $script->description;
            $new->language = $script->language;
            $new->code = $script->code;
            $new->created_at = $this->formatDate($script->created_at);
            $new->save();

            // save categories
            if (isset($script->categories)) {
                foreach ($script->categories as $categoryDef) {
                    $category = $this->saveCategory('script', $categoryDef);
                    $new->categories()->save($category);
                }
            }

            return $new;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * If an existing ProcessCategory does not exist with the same name, create
     * a new ProcessCategory model based on the object from the imported file,
     * then save it to the database.
     *
     * @param object $processCategory
     *
     * @return void
     */
    protected function saveCategory($type, $category)
    {
        if (!array_key_exists($type . '_categories', $this->new)) {
            $this->new[$type . '_categories'] = [];
        }

        // use ProcessMaker\Models\ProcessCategory;
        $class = '\\ProcessMaker\\Models\\' . ucfirst($type) . 'Category';

        try {
            $existing = $class::where('name', $category->name)->first();
            $this->prepareStatus($type . '_categories', true);
            if ($existing) {
                $this->new[$type . '_categories'][] = $existing;
                $new = $existing;
            } else {
                $new = new $class;
                $new->name = $category->name;
                $new->status = $category->status;
                $new->created_at = $this->formatDate($category->created_at);
                $new->save();

                $this->new[$type . '_categories'][] = $new;
            }
            $this->finishStatus($type . '_categories');

            return $new;
        } catch (\Exception $e) {
            $this->finishStatus($type . '_categories', true);

            return null;
        }
    }

    /**
     * Create a new Process model based on the object from the imported file,
     * then save it to the database.
     *
     * @param object $process
     *
     * @return void
     */
    private function saveProcess($process)
    {
        try {
            $this->prepareStatus('process', true);
            $new = new Process;
            $new->process_category_id = collect($this->new['process_categories'])->pluck('id')->join(',');
            $new->user_id = $this->currentUser()->id;
            $new->bpmn = $process->bpmn;
            $new->description = $process->description;
            $new->name = $this->formatName($process->name, 'name', Process::class);
            $new->status = $process->status;
            $new->created_at = $this->formatDate($process->created_at);
            $new->deleted_at = $this->formatDate($process->deleted_at);
            $new->properties = isset($process->properties) ? (array) $process->properties : null;
            $new->save();
            $this->newProcessId = $new->id;

            if (property_exists($process, 'notifications')) {
                foreach ($process->notifications as $notifiable => $notificationTypes) {
                    foreach ($notificationTypes as $notificationType => $wanted) {
                        if ($wanted === true) {
                            $newNotification = new ProcessNotificationSetting;
                            $newNotification->process_id = $new->id;
                            $newNotification->notifiable_type = $notifiable;
                            $newNotification->notification_type = $notificationType;
                            $newNotification->save();
                        }
                    }
                }
            }

            if (property_exists($process, 'task_notifications')) {
                foreach ($process->task_notifications as $elementId => $notifiables) {
                    foreach ($notifiables as $notifiable => $notificationTypes) {
                        foreach ($notificationTypes as $notificationType => $wanted) {
                            if ($wanted === true) {
                                $newNotification = new ProcessNotificationSetting;
                                $newNotification->process_id = $new->id;
                                $newNotification->element_id = $elementId;
                                $newNotification->notifiable_type = $notifiable;
                                $newNotification->notification_type = $notificationType;
                                $newNotification->save();
                            }
                        }
                    }
                }
            }

            $this->definitions = $new->getDefinitions();
            $this->new['process'] = $new;
            $this->finishStatus('process');
        } catch (\Exception $e) {
            throw $e;
            $this->finishStatus('process', true);
        }
    }

    /**
     * Handle the edge case of packages that have been renamed but are still
     * referenced in old files.
     *
     * @param string $package
     *
     * @return bool
     */
    private function isBackwardCompatiblePackage($package)
    {
        if (array_key_exists($package, $this->backwardCompatiblePackageMap)) {
            return $this->isRegisteredPackage($this->backwardCompatiblePackageMap[$package]);
        }

        return false;
    }

    private function validatePackages($process)
    {
        try {
            $response = true;

            $new = new Process;
            $new->bpmn = $process->bpmn;
            $definitions = $new->getDefinitions();
            $packages = [];

            $tasks = $definitions->getElementsByTagName('serviceTask');
            foreach ($tasks as $task) {
                $implementation = $task->getAttribute('implementation');
                if ($implementation) {
                    $implementation = explode('/', $implementation);
                    if (!in_array($implementation[0], $packages, true)) {
                        $packages[] = $implementation[0];
                        $exists = $this->isRegisteredPackage($implementation[0]);
                        if (!$exists) {
                            $exists = $this->isBackwardCompatiblePackage($implementation[0]);
                        }
                        $response = $exists === false ? false : $response;
                        $package = [];
                        $package['label'] = $implementation[0];
                        $package['success'] = $exists;
                        $package['message'] = $exists ? __('Package installed') : __('The package is not installed');
                        $this->status = array_merge([$implementation[0] => $package], $this->status);
                    }
                }
            }

            return $response;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Save the BPMN with any adjustments that have been made along the way.
     *
     * @return void
     */
    private function saveBpmn($process)
    {
        $this->new['process']->bpmn = $this->definitions->saveXML();
        if (isset($process->cancel_screen_id)) {
            $this->new['process']->cancel_screen_id = $process->cancel_screen_id;
        }
        if (isset($process->request_detail_screen_id)) {
            $this->new['process']->request_detail_screen_id = $process->request_detail_screen_id;
        }

        $manager = app(ExportManager::class);
        $manager->updateReferences($this->new);
        $this->status = array_merge($this->status, $manager->getLogMessages());
        $this->new['process']->save();
    }

    /**
     * Parse files with version 1
     *
     * @return object
     */
    private function parseFileV1()
    {
        if (!$this->validatePackages($this->file->process)) {
            return (object) [
                'status' => collect($this->status),
                'assignable' => [],
                'process' => [],
            ];
        }

        if (isset($this->file->process_category)) {
            $this->saveCategory('process', $this->file->process_category);
        }
        if (isset($this->file->process_categories)) {
            foreach ($this->file->process_categories as $category) {
                $this->saveCategory('process', $category);
            }
        }

        $this->saveProcess($this->file->process);
        $this->saveScripts($this->file->scripts);
        $this->saveScreens($this->file->screens, $this->file->process);
        $this->parseAssignables();
        $this->setAnonymousUser();
        $this->saveBpmn($this->file->process);

        return (object) [
            'status' => collect($this->status),
            'assignable' => $this->assignable,
            'process' => $this->new['process'],
        ];
    }

    private function parseFileV2()
    {
    }

    /**
     * Replace any anonymous user placeholders with the anonymous user id
     *
     * @return void
     */
    private function setAnonymousUser()
    {
        $humanTasks = ['startEvent', 'task', 'userTask', 'manualTask'];
        $ns = WorkflowServiceProvider::PROCESS_MAKER_NS;

        if (!isset($this->file->process->anonymousUserId)) {
            return;
        }

        $originalAnonymousUserId = $this->file->process->anonymousUserId;

        foreach ($humanTasks as $tag) {
            foreach ($this->definitions->getElementsByTagName($tag) as $task) {
                $assignedUsers = $task->getAttributeNS($ns, 'assignedUsers');
                if ($assignedUsers === (string) $originalAnonymousUserId) {
                    $task->setAttributeNS($ns, 'assignedUsers', app(AnonymousUser::class)->id);
                }
            }
        }
    }

    /**
     * Decode the file from base64 and JSON.
     *
     * @return void
     */
    protected function decodeFile()
    {
        if (substr($this->fileContents, 0, 1) === '{' && (bool) json_decode($this->fileContents)) {
            $this->file = json_decode($this->fileContents);
        } else {
            $this->file = base64_decode($this->fileContents);
            $this->file = json_decode($this->file);
        }

        //replace tag prefix bpmn2 to bpmn
        if (property_exists($this->file, 'process') && property_exists($this->file->process, 'bpmn')) {
            $this->file->process->bpmn = str_replace('bpmn2', 'bpmn', $this->file->process->bpmn);
        }
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        if ($this->path && !$this->fileContents) {
            $this->fileContents = Storage::get($this->path);
        }
        //First, decode the file
        $this->decodeFile();

        //Then, process it based on version number
        if ($this->file->type == 'process_package') {
            if ($method = $this->getParser()) {
                $this->resetStatus();
                $response = $this->{$method}();
                $this->broadcastResponse($response);

                return $response;
            }
        }

        //Return false by default
        return false;
    }

    /**
     * Initial state of the process
     */
    protected function resetStatus()
    {
        $this->status = [];

        $this->status['process_categories'] = [
            'label' => __('Process Categories'),
            'success' => false,
            'message' => __('Starting'), ];

        $this->status['process'] = [
            'label' => __('Process'),
            'success' => false,
            'message' => __('Starting'), ];

        $this->status['scripts'] = [
            'label' => __('Scripts'),
            'success' => false,
            'message' => __('Starting'), ];

        $this->status['screens'] = [
            'label' => __('Screens'),
            'success' => false,
            'message' => __('Starting'), ];
    }

    /**
     * Prepare information status
     *
     * @param $element
     * @param $data boolean
     */
    protected function prepareStatus($element, $data = false)
    {
        $this->status[$element]['message'] = __('Started import of');
        if ($data) {
            $this->status[$element]['message'] = __('Incomplete import of');
        }
    }

    /**
     * Finish status
     *
     * @param $element
     * @param $error
     */
    protected function finishStatus($element, $error = false, $payload = null)
    {
        $label = ucwords(implode(' ', explode('_', $element)));
        $this->status[$element]['label'] = __($label);
        $this->status[$element]['success'] = true;
        if (!is_null($payload)) {
            $uuids = [];
            foreach ($payload as $key => $value) {
                array_push($uuids, $value->uuid);
            }
            $this->status[$element]['uuids'] = $uuids;
        }
        $this->status[$element]['message'] = __('Successfully imported');
        if ($error) {
            $this->status[$element]['success'] = false;
            $this->status[$element]['message'] = __('Unable to import');
        }
    }

    /**
     * Returns the list of watchers to be imported
     * @param $screen
     * @return array
     */
    protected function watcherScriptsToSave($screen)
    {
        if (empty($screen->watchers)) {
            return null;
        }

        $watcherList = [];
        foreach ($screen->watchers as $watcher) {
            $script = $watcher->script;
            $watcher->script_id = $script->id;
            $watcher->script->title = $script->title;
            $watcherList[] = $watcher;
        }

        return $watcherList;
    }

    /**
     * Determine if the call activity is a wrapper around a package
     *
     * @param $task
     * @return bool
     */
    private function isCallActivityFromPackage($task)
    {
        $configString = $task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'config');
        $config = json_decode($configString, true);
        if (isset($config['calledElement'])) {
            // calledElement only exists on call activities, not packages
            return false;
        }

        return true;
    }

    private function broadcastResponse($response)
    {
        if ($this->user) {
            User::find($this->user)->notify(new ImportReady(
                $this->code,
                json_decode(json_encode($response), true)
            ));
        }
    }
}
