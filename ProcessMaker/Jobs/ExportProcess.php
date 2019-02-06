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
use ProcessMaker\Providers\WorkflowServiceProvider;

class ExportProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $process, $definitions, $filePath, $file;
    
    private $package = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Process $process, $filePath = null)
    {
        $this->process = $process;
        $this->definitions = $this->process->getDefinitions();
        $this->filePath = $filePath;
    }
    
    private function packageProcess()
    {
        $this->package['process'] = $this->process->toArray();
        $this->package['process']['bpmn'] = $this->process->bpmn;    
    }
    
    private function packageProcessCategory()
    {
        $this->package['process_category'] = $this->process->category->toArray();
    }
    
    private function packageScreens()
    {
        $this->package['screens'] = [];
        
        $screenIds = [];
        
        $humanTasks = ['task', 'userTask'];        
        foreach($humanTasks as $humanTask) {
            $tasks = $this->definitions->getElementsByTagName($humanTask);
            foreach ($tasks as $task) {
                $screenRef = $task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'screenRef');
                $screenIds[] = $screenRef;
            }
        }
        
        if (count($screenIds)) {
            $screens = Screen::whereIn('id', $screenIds)->get();
            
            $screens->each(function($screen) {
                $this->package['screens'][] = $screen->toArray();
            });
        }
    }

    private function packageScripts()
    {
        $this->package['scripts'] = [];

        $scriptIds = [];
        
        $tasks = $this->definitions->getElementsByTagName('scriptTask');
        foreach ($tasks as $task) {
            $scriptRef = $task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'scriptRef');
            $scriptIds[] = $scriptRef;
        }

        if(count($scriptIds)) {
            $scripts = Script::whereIn('id', $scriptIds)->get();

            $scripts->each(function($scripts) {
                $this->package['scripts'][] = $scripts->toArray();
            });
        }
    }

    private function packageAssignmentTypes()
    {
        $bpmn = $this->package['process']['bpmn'];

        $bpmn = preg_replace('/(pm:assignedUsers="\d+")/', '', $bpmn);
        $bpmn = preg_replace('/(pm:assignedGroups="\d+")/', '', $bpmn);
        
        $this->package['process']['bpmn'] = $bpmn;
    }
    
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
    
    private function packageFile()
    {
        $this->package['type'] = 'process_package';
        $this->package['version'] = '1';
        $this->packageProcess();
        $this->packageProcessCategory();
        $this->packageScreens();
        $this->packageScripts();
        $this->packageAssignmentTypes();
        $this->packageEnvironmentVariables();
    }
    
    private function encodeFile()
    {
        $this->file = json_encode($this->package);
        $this->file = base64_encode($this->file);
    }

    /**
     * Save the file .
     *
     * @return void
     */
    private function saveFile()
    {
        $bytes = file_put_contents($this->filePath, $this->file);
        if ($bytes !== false) {
            return true;
        }
    }
    
    private function cacheFile()
    {
        $key = sha1($this->file);
        $value = $this->file;
        $expiresAt = now()->addHours(1);
        Cache::put($key, $value, $expiresAt);
        
        return $key;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->packageFile();
        $this->encodeFile();
        
        if ($this->filePath) {
            return $this->saveFile();
        } else {
            return $this->cacheFile();
        }
    }
}
