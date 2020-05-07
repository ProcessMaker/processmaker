<?php

namespace ProcessMaker\Jobs;

use Cache;
use Illuminate\Bus\Queueable;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use ProcessMaker\Managers\ExportManager;
use ProcessMaker\Providers\WorkflowServiceProvider;

class ExportProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The process we will export.
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

    /**
     * The path where our final file should be stored, if any.
     *
     * @var string|null
     */
    protected $filePath;

    /**
     * The final contents of our file.
     *
     * @var string
     */
    private $fileContents;

    /**
     * The array which will contain all of our packaged objects in array form.
     *
     * @var array[]
     */
    protected $package = [];

    /**
     * @var ExportManager
     */
    public $manager;


    /**
     * Create a new job instance, set the process, get BPMN definitions, and
     * set the file path.
     *
     * @return void
     */
    public function __construct(Process $process, $filePath = null)
    {
        $this->process = $process;
        $this->definitions = $this->process->getDefinitions();
        $this->filePath = $filePath;
    }

    /**
     * Parse the BPMN, looking for any task assignments to users and/or groups,
     * then remove them along with any referenced users or groups.
     *
     * @return void
     */
    private function removeAssignedEntities()
    {
        $humanTasks = ['startEvent', 'task', 'userTask', 'manualTask'];
        foreach ($humanTasks as $humanTask) {
            $tasks = $this->definitions->getElementsByTagName($humanTask);
            foreach ($tasks as $task) {
                $task->removeAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignment');
                $task->removeAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignedUsers');
                $task->removeAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignedGroups');
            }
        }

        //remove assignments to call Activity
        $callActivity = $this->definitions->getElementsByTagName('callActivity');
        foreach ($callActivity as $task) {
            $task->removeAttribute('calledElement');
        }

        $this->process->bpmn = $this->definitions->saveXML();
    }

    /**
     * Package the process itself. Note that we must save BPMN separately
     * since it is hidden from our toArray method.
     *
     * @return void
     */
    private function packageProcess()
    {
        $this->package['process'] = $this->process->append('notifications', 'task_notifications')->toArray();
        $this->package['process']['bpmn'] = $this->process->bpmn;
    }

    /**
     * Package the process category associated with our process.
     *
     * @return void
     */
    private function packageProcessCategory()
    {
        $this->package['process_categories'] = $this->process->categories->toArray();
    }

    /**
     * Package any screens referred to in our BPMN.
     *
     * @return void
     */
    public function packageScreens()
    {
        $this->package['screens'] = [];
        $this->package['screen_categories'] = [];

        if (! isset($this->screen)) {
            $screenIds = $this->manager->getDependenciesOfType(Screen::class, $this->process, []);
        } else {
            $screenIds = array_merge([$this->screen->id], $this->manager->getDependenciesOfType(Screen::class, $this->screen, []));
        }

        if (count($screenIds)) {
            $screens = Screen::whereIn('id', $screenIds)->get();
            $screens->each(function ($screen) {
                $this->packageScreen($screen);
            });
        }
    }

    private function packageScreen(Screen $screen)
    {
        $screenArray = $screen->toArray();
        $screenArray['categories'] = $screen->categories->toArray();
        $this->package['screens'][] = $screenArray;
    }

    /**
     * Package any scripts referred to in our BPMN.
     *
     * @return void
     */
    private function packageScripts()
    {
        $this->package['scripts'] = [];

        $scriptIds = $this->manager->getDependenciesOfType(Script::class, $this->process, []);
        if (count($scriptIds)) {
            $scripts = Script::whereIn('id', $scriptIds)->get();

            $scripts->each(function ($script) {
                $scriptArray = $script->toArray();
                $scriptArray['categories'] = $script->categories->toArray();
                $this->package['scripts'][] = $scriptArray;
            });
        }
    }

    /**
     * Package the metadata (NOT THE VALUE) of any environment variables
     * referred to in our scripts.
     *
     * @return void
     */
    private function packageEnvironmentVariables()
    {
        $this->package['environment_variables'] = [];
        $environmentVariables = EnvironmentVariable::get();

        foreach ($environmentVariables as $environmentVariable) {
            foreach ($this->package['scripts'] as $script) {
                $position = strpos($script['code'], $environmentVariable->name);
                if ($position !== false) {
                    $this->package['environment_variables'][] = $environmentVariable->toArray();
                }
            }
        }
    }

    /**
     * Run through each step of the packaging process. We specify a file type
     * and a file version in case of future changes to the file format.
     *
     * @return void
     */
    private function packageFile()
    {
        $this->package['type'] = 'process_package';
        $this->package['version'] = '1';
        $this->removeAssignedEntities();
        $this->packageProcess();
        $this->packageProcessCategory();
        $this->packageScreens();
        $this->packageScripts();
        $this->packageEnvironmentVariables();
    }

    /**
     * Encode the file to JSON and base64.
     *
     * @return void
     */
    protected function encodeFile()
    {
        $this->fileContents = json_encode($this->package);
    }

    /**
     * Save the file to the specified path, then return true on success or
     * false upon failure.
     *
     * @return boolean
     */
    protected function saveFile()
    {
        $bytes = file_put_contents($this->filePath, $this->fileContents);
        if ($bytes !== false) {
            return true;
        }
    }

    /**
     * Save the file to the cache, then return the cache key.
     *
     * @return string
     */
    protected function cacheFile()
    {
        $key = sha1($this->fileContents);
        $value = $this->fileContents;
        $expiresAt = now()->addHours(1);
        Cache::put($key, $value, $expiresAt);

        return $key;
    }

    /**
     * Execute the job.
     *
     * @return boolean|string
     */
    public function handle()
    {
        $this->manager = app(ExportManager::class);
        // Package up our process
        $this->packageFile();

        // Encode the file
        $this->encodeFile();

        // If a specific file path is specified,
        // export to it and return true. Otherwise,
        // save to our cache and return the saved key.
        if ($this->filePath) {
            return $this->saveFile();
        } else {
            return $this->cacheFile();
        }
    }
}
