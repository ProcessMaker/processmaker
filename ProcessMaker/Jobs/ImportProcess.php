<?php

namespace ProcessMaker\Jobs;

use Auth;
use Cache;
use DB;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use ProcessMaker\Providers\WorkflowServiceProvider;

class ImportProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $process, $definitions, $fileContents, $file;
    
    private $package = [];
    
    private $new = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($fileContents)
    {
        $this->fileContents = $fileContents;
    }
    
    private function getParser()
    {
        $method = "parseFileV{$this->file->version}";
        if (method_exists($this, $method)) {
            return $method;
        } else {
            return false;
        }
    }
    
    private function currentUser()
    {
        if (! app()->runningInConsole()) {
            return Auth::user();
        } else {
            return User::first();
        }
    }
    
    private function formatDate($date)
    {
        if ($date) {
            return new Carbon($date);
        } else {
            return null;
        }
    }

    private function formatName($name, $field, $class)
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
            
            //Return the name appended with the number
            return $name . ' ' . $number;
        } else {
            //Return the original name (if there are no dupes)
            return $name;
        }
    }
    
    private function removeAssignedEntities()
    {
        $humanTasks = ['task', 'userTask'];
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
    
    private function saveEnvironmentVariables($environmentVariables)
    {
        $this->new['environment_variables'] = [];
                
        foreach ($environmentVariables as $environmentVariable) {
            //Find duplicates of the environment variable's name
            $dupe = EnvironmentVariable::where('name', $environmentVariable->name)->get();        

            //If no duplicate, save it!
            if (! $dupe->count()) {
                $new = new EnvironmentVariable;
                $new->name = $environmentVariable->name;
                $new->description = $environmentVariable->description;
                $new->value = '';
                $new->created_at = $this->formatDate($environmentVariable->created_at);
                $new->save();
            }
        }
    }
    
    private function updateScreenRefs($oldId, $newId)
    {
        $humanTasks = ['task', 'userTask'];
        foreach ($humanTasks as $humanTask) {
            $tasks = $this->definitions->getElementsByTagName($humanTask);
            foreach ($tasks as $task) {
                $screenRef = $task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'screenRef');
                if ($screenRef == $oldId) {
                    $task->setAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'screenRef', $newId);
                }
            }
        }
    }
    
    private function saveScreens($screens)
    {
        $this->new['screens'] = [];
        
        foreach ($screens as $screen) {
            $new = new Screen;
            $new->title = $this->formatName($screen->title, 'title', Screen::class);
            $new->description = $screen->description;
            $new->type = $screen->type;
            $new->config = $screen->config;
            $new->computed = $screen->computed;
            $new->created_at = $this->formatDate($screen->created_at);
            $new->save();
            
            $this->updateScreenRefs($screen->id, $new->id);
            
            $this->new['screens'][] = $new;
        }
    }

    private function updateScriptRefs($oldId, $newId)
    {
        $tasks = $this->definitions->getElementsByTagName('scriptTask');
        foreach ($tasks as $task) {
            $scriptRef = $task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'scriptRef');        
            if ($scriptRef == $oldId) {
                $task->setAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'scriptRef', $newId);
            }
        }
    }

    private function saveScripts($scripts)
    {
        $this->new['scripts'] =[];

        foreach($scripts as $script) {
            $new = new Script;
            $new->title = $this->formatName($script->title, 'title', Script::class);
            $new->description = $script->description;
            $new->language = $script->language;
            $new->code = $script->code;
            $new->created_at = $this->formatDate($script->created_at);
            $new->save();

            $this->updateScriptRefs($script->id, $new->id);
            
            $this->new['scripts'][] = $new;
        }
    }
    
    private function saveProcessCategory($processCategory)
    {
        $existing = ProcessCategory::where('name', $processCategory->name)->first();
        if ($existing) {
            $this->new['process_category'] = $existing;
        } else {
            $new = new ProcessCategory;
            $new->name = $processCategory->name;
            $new->status = $processCategory->status;
            $new->created_at = $this->formatDate($processCategory->created_at);
            $new->save();
            
            $this->new['process_category'] = $new;
        }
    }
    
    private function saveProcess($process)
    {
        $new = new Process;
        $new->process_category_id = $this->new['process_category']->id;
        $new->user_id = $this->currentUser()->id;
        $new->bpmn = $process->bpmn;
        $new->description = $process->description;
        $new->name = $this->formatName($process->name, 'name', Process::class);
        $new->status = $process->status;
        $new->created_at = $this->formatDate($process->created_at);
        $new->deleted_at = $this->formatDate($process->deleted_at);
        $new->save();

        $this->definitions = $new->getDefinitions();       
        $this->new['process'] = $new;
    }
    
    private function saveBpmn()
    {
        $this->new['process']->bpmn = $this->definitions->saveXML();
        $this->new['process']->save();
    }
    
    private function parseFileV1()
    {
        $this->saveEnvironmentVariables($this->file->environment_variables);
        $this->saveProcessCategory($this->file->process_category);
        $this->saveProcess($this->file->process);
        $this->saveScripts($this->file->scripts);
        $this->saveScreens($this->file->screens);
        $this->removeAssignedEntities();
        $this->saveBpmn();
        return true;
    }

    private function decodeFile()
    {
        $this->file = base64_decode($this->fileContents);
        $this->file = json_decode($this->file);
    }
    
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->decodeFile();

        if ($this->file->type == 'process_package') {
            if ($method = $this->getParser()) {
                return $this->{$method}();
            }
        }
        
        return false;
    }
}
