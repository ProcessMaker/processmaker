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
use ProcessMaker\Models\ProcessNotificationSetting;
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

    /**
     * The original contents of the imported file.
     *
     * @var string
     */    
    private $fileContents;

    /**
     * The decoded object obtained from the file.
     *
     * @var object
     */
    private $file;

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

    /**
     * An array of the new models being created on import.
     *
     * @var object[]
     */    
    private $new = [];

    /**
     * Create a new job instance and set the file contents.
     *
     * @return void
     */
    public function __construct($fileContents)
    {
        $this->fileContents = $fileContents;
    }

    /**
     * Return a valid method name based on the version of the imported file,
     * or return false if no such method exists.
     *
     * @return string|bool
     */    
    private function getParser()
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
        if (! app()->runningInConsole()) {
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
    private function formatDate($date)
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

    /**
     * Parse the BPMN, looking for any task assignments to users and/or groups,
     * then remove them along with any referenced users or groups.
     *
     * @return void
     */        
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

    /**
     * Create a new EnvironmentVariable model for each environment variable
     * object in the imported file, then save it to the database if it
     * does not match the name of an existing environment variable.
     *
     * @param object[] $environmentVariables
     *
     * @return void
     */    
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

    /**
     * Pass an old screen ID and a new screen ID, then replace any references
     * within the BPMN to the old ID with the new ID.
     *
     * @param string|integer $oldId
     * @param string|integer $newId
     *
     * @return void
     */    
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

    /**
     * Create a new Screen model for each screen object in the imported file,
     * then save it to the database.
     *
     * @param object[] $screens
     *
     * @return void
     */    
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

    /**
     * Pass an old script ID and a new script ID, then replace any references
     * within the BPMN to the old ID with the new ID.
     *
     * @param string|integer $oldId
     * @param string|integer $newId
     *
     * @return void
     */
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

    /**
     * If an existing ProcessCategory does not exist with the same name, create
     * a new ProcessCategory model based on the object from the imported file,
     * then save it to the database.
     *
     * @param object $processCategory
     *
     * @return void
     */    
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
    }

    /**
     * Save the BPMN with any adjustments that have been made along the way.
     *
     * @return void
     */    
    private function saveBpmn()
    {
        $this->new['process']->bpmn = $this->definitions->saveXML();
        $this->new['process']->save();
    }

    /**
     * Parse files with version 1
     *
     * @return boolean
     */    
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

    /**
     * Decode the file from base64 and JSON.
     *
     * @return void
     */
    private function decodeFile()
    {
        $this->file = base64_decode($this->fileContents);
        $this->file = json_decode($this->file);
    }
    
    /**
     * Execute the job.
     *
     * @return boolean
     */
    public function handle()
    {
        //First, decode the file
        $this->decodeFile();

        //Then, process it based on version number
        if ($this->file->type == 'process_package') {
            if ($method = $this->getParser()) {
                return $this->{$method}();
            }
        }
        
        //Return false by default
        return false;
    }
}
