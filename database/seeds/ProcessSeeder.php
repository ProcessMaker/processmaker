<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\WorkflowServiceProvider;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;

class ProcessSeeder extends Seeder
{

    /**
     * Array of [language => mime-type]
     */
    const mimeTypes = [
        'javascript' => 'application/javascript',
        'lua' => 'application/x-lua',
        'php' => 'application/x-php',
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //load user admin
        $admin = User::where('username', 'admin')->firstOrFail();

        foreach (glob(database_path('processes') . '/*.bpmn') as $filename) {
            echo 'Creating: ', $filename, "\n";
            $process = factory(Process::class)->make([
                'bpmn' => file_get_contents($filename),
                'user_id' => $admin->getKey(),
                'status' => 'ACTIVE',
            ]);
            //Load the process title from the the main process of the BPMN definition
            $processes = $process->getDefinitions()->getElementsByTagName('process');
            if ($processes->item(0)) {
                $processDefinition = $processes->item(0)->getBpmnElementInstance();
                if (!empty($processDefinition->getName())) {
                    $process->name = $processDefinition->getName();
                }
            }
            //Or load the process title from the collaboration of the BPMN definition
            $collaborations = $process->getDefinitions()->getElementsByTagName('collaboration');
            if ($collaborations->item(0)) {
                $collaborationDefinition = $collaborations->item(0)->getBpmnElementInstance();
                if (!empty($collaborationDefinition->getName())) {
                    $process->name = $collaborationDefinition->getName();
                }
            }
            $process->save();

            $definitions = $process->getDefinitions();

            //Create scripts from the BPMN process definition
            $scriptTasks = $definitions->getElementsByTagName('scriptTask');
            foreach ($scriptTasks as $scriptTaskNode) {
                $scriptTask = $scriptTaskNode->getBpmnElementInstance();
                //Create a row in the Scripts table
                $script = factory(Script::class)->create([
                    'title' => $scriptTask->getName('name') . ' Script',
                    'code' => $scriptTaskNode->getElementsByTagName('script')->item(0)->nodeValue,
                    'language' => $this->languageOfMimeType($scriptTask->getScriptFormat()),
                ]);
                $scriptTaskNode->setAttributeNS(
                    WorkflowServiceProvider::PROCESS_MAKER_NS, 'scriptRef', $script->id
                );
                $scriptTaskNode->setAttributeNS(
                    WorkflowServiceProvider::PROCESS_MAKER_NS, 'config', '{}'
                );
            }

            //Create/Assign Users to tasks
            $lanes = $definitions->getElementsByTagName('lane');
            foreach($lanes as $nodeLane) {
                $lane = $nodeLane->getBpmnElementInstance();
                $user = $this->getUserOrCreate($lane->getName());
                foreach($lane->getFlowNodes() as $node) {
                    if ($node instanceof ActivityInterface && !($node instanceof ScriptTaskInterface)) {
                        factory(ProcessTaskAssignment::class)->create([
                            'process_id' => $process->getKey(),
                            'process_task_id' => $node->getId(),
                            'assignment_id' => $user->getKey(),
                            'assignment_type' => User::class,
                        ]);
                    }
                }
            }

            //Add screens to the process
            $admin = User::where('username', 'admin')->firstOrFail();
            $humanTasks = ['task', 'userTask'];
            foreach($humanTasks as $humanTask) {
                $tasks = $definitions->getElementsByTagName($humanTask);
                foreach($tasks as $task) {
                    $screenRef = $task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'screenRef');
                    $id = $task->getAttribute('id');
                    if ($screenRef) {
                        $screen = $this->createScreen($id, $screenRef, $process);
                        $task->setAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'screenRef', $screen->getKey());
                    }
                    //Assign "admin" to the task if it does not have user assigned
                    $assignments = ProcessTaskAssignment::where('process_id', $process->getKey())
                        ->where('process_task_id', $id)
                        ->count();
                    if (!$assignments) {
                        factory(ProcessTaskAssignment::class)->create([
                            'process_id' => $process->getKey(),
                            'process_task_id' => $id,
                            'assignment_id' => $admin->getKey(),
                            'assignment_type' => User::class,
                        ]);
                    }
                }
            }

            //Update the screen and script references in the BPMN of the process
            $process->bpmn = $definitions->saveXML();
            $process->save();
        }
    }

    /**
     * Load the JSON of a screen.
     *
     * @param string $id
     * @param string $screenRef
     * @param string $process
     *
     * @return Screen
     */
    private function createScreen($id, $screenRef, $process) {

        if (file_exists(database_path('processes/screens/' . $screenRef . '.json'))) {
            $json = json_decode(file_get_contents(database_path('processes/screens/' . $screenRef . '.json')));
            return factory(Screen::class)->create([
                        'title' => $json[0]->name,
                        'config' => $json
            ]);
        } elseif (file_exists(database_path('processes/screens/' . $id . '.json'))) {
            $json = json_decode(file_get_contents(database_path('processes/screens/' . $id . '.json')));
            return factory(Screen::class)->create([
                        'title' => $json[0]->name,
                        'config' => $json,
            ]);
        }
    }

    /**
     * Get the language that corresponds to an specific mime-type.
     *
     * @param string $mime
     *
     * @return string
     */
    private function languageOfMimeType($mime)
    {
        return in_array($mime, self::mimeTypes) ? array_search($mime, self::mimeTypes) : '';
    }

    /**
     * Format name without spaces and to lowercase
     *
     * @param $name
     *
     * @return string
     */
    private function formatName($name)
    {
        return strtolower(str_replace(' ', '.', $name));
    }

    /**
     * Get or create a user by full name.
     *
     * @param string $userFullName
     *
     * @return User
     */
    private function getUserOrCreate($userFullName)
    {
        $name = $this->formatName($userFullName);
        $user = User::where('username', $name)
            ->first();
        if (!$user) {
            $user = factory(User::class)->create([
                'username' => $name,
                'password' => Hash::make('admin'),
                'status' => 'ACTIVE',
                'is_administrator' => true
            ]);
        }

        return $user;
    }

    /**
     * Get or create a group by name
     *
     * @param $name
     *
     * @return mixed
     */
    private function getGroupOrCreate($name)
    {
        $group = Group::where('name', $name)->first();
        if (!$group) {
            $group = factory(Group::class)->create([
                'name' => $name,
                'status' => 'ACTIVE'
            ]);
        }
        factory(GroupMember::class)->create( [
            'member_id' => function () use ($name) {
                return $this->getUserOrCreate($name)->getKey();
            },
            'member_type' => User::class,
            'group_id' => $group->getKey()
        ]);
        return $group;
    }
}
