<?php

namespace ProcessMaker\Jobs;

use Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Http\Resources\V1_1\TaskScreen;
use ProcessMaker\Managers\ExportManager;
use ProcessMaker\Models\AnonymousUser;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use ProcessMaker\Providers\WorkflowServiceProvider;
use Symfony\Component\Yaml\Yaml;

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
        $ns = WorkflowServiceProvider::PROCESS_MAKER_NS;

        foreach ($humanTasks as $humanTask) {
            $tasks = $this->definitions->getElementsByTagName($humanTask);
            foreach ($tasks as $task) {
                if ($this->assignedToAnonymous($task)) {
                    // Do not remove the anonymous user association. It will be
                    // updated when importing.
                    continue;
                }

                // Only remove user/group assignments
                $assignment = $task->getAttributeNS($ns, 'assignment');
                if (in_array($assignment, ['user', 'group', 'user_group'])) {
                    $task->removeAttributeNS($ns, 'assignment');
                }
                if (!in_array($assignment, ['user_by_id'])) {
                    $task->removeAttributeNS($ns, 'assignedUsers');
                }
                $task->removeAttributeNS($ns, 'assignedGroups');
            }
        }

        //remove assignments to call Activity
        $callActivities = $this->definitions->getElementsByTagName('callActivity');
        foreach ($callActivities as $task) {
            $callActivity = $task->getBPMNElementInstance();
            $external = $callActivity->isFromExternalDefinition();
            $service = $callActivity->isServiceSubProcess();
            if ($external && !$service) {
                $task->removeAttribute('calledElement');
            }
        }

        $this->process->bpmn = $this->definitions->saveXML();

        if (!is_array($this->process->properties)) {
            $this->process->properties = [];
        }

        $properties = $this->process->properties;
        $properties['manager_id'] = null;
        $this->process->properties = $properties;
    }

    private function assignedToAnonymous($task)
    {
        $ns = WorkflowServiceProvider::PROCESS_MAKER_NS;
        $assignment = $task->getAttributeNS($ns, 'assignment');
        $assignedUsers = $task->getAttributeNS($ns, 'assignedUsers');

        return in_array($assignment, ['user', 'user_group']) &&
               $assignedUsers === (string) app(AnonymousUser::class)->id;
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
        $this->package['process']['anonymousUserId'] = app(AnonymousUser::class)->id;
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

        if (!isset($this->screen)) {
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
    public function packageScripts()
    {
        $this->package['scripts'] = [];

        if (!isset($this->screen)) {
            $scriptIds = $this->manager->getDependenciesOfType(Script::class, $this->process, []);
        } else {
            $scriptIds = $this->manager->getDependenciesOfType(Script::class, $this->screen, []);
        }

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
     * @return bool
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
     * @return bool|string
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

    public function getFileContents()
    {
        return $this->fileContents;
    }

    public function prepareForDevLink()
    {
        $rawContent = json_decode($this->fileContents, true);
        $content = $rawContent['screens'][0];
        // HERE: Reeplace the IDs by UUIDs
        unset($content['id']);
        unset($content['screen_category_id']);
        unset($content['projects']);
        $content['categories'] = $this->convertToUUIDReferences($content['categories']);

        // Prepare the content for DevLink
        $content['config'] = TaskScreen::removeInspectorFromScreenMetadata($content['config']);
        
        // return json_encode($rawContent, JSON_PRETTY_PRINT);
        // Convert result to YAML
        return Yaml::dump($content, 16, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
    }

    private function convertToUUIDReferences(array $references): array
    {
        $response = [];
        foreach ($references as $reference) {
            $response[] = $reference['uuid'];
        }

        return $response;
    }
}
