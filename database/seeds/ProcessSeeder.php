<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Model\EnvironmentVariable;
use ProcessMaker\Model\Form;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Script;
use ProcessMaker\Providers\WorkflowServiceProvider;

class ProcessSeeder extends Seeder
{

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
        foreach (glob(database_path('processes') . '/*.bpmn') as $filename) {
            $process = factory(Process::class)->make([
                'bpmn' => file_get_contents($filename),
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

            //Create scripts from the BPMN process definition
            $scriptTasks = $process->getDefinitions()->getElementsByTagName('scriptTask');
            foreach ($scriptTasks as $scriptTaskNode) {
                $scriptTask = $scriptTaskNode->getBpmnElementInstance();
                //Create a row in the Scripts table
                $script = factory(Script::class)->create([
                    'title' => $scriptTask->getName('name') . ' Script',
                    'code' => $scriptTaskNode->getElementsByTagName('script')->item(0)->nodeValue,
                    'language' => $this->languageOfMimeType($scriptTask->getScriptFormat()),
                    'process_id' => $process->id,
                ]);
                $scriptTaskNode->setAttributeNS(
                    WorkflowServiceProvider::PROCESS_MAKER_NS, 'scriptRef', $script->uid
                );
                $scriptTaskNode->setAttributeNS(
                    WorkflowServiceProvider::PROCESS_MAKER_NS, 'scriptConfiguration', '{}'
                );
            }
            //Update the script references in the BPMN of the process
            $process->bpmn = $process->getDefinitions()->saveXML();
            $process->save();

            $definitions = $process->getDefinitions();

            //Add forms to the process
            $json = $this->loadForm('request.json');
            factory(Form::class)->create([
                'uid' => $definitions->getActivity('request')->getProperty('formRef'),
                'title' => $json[0]->name,
                'content' => $json,
                'process_id' => $process->id,
            ]);

            $json = $this->loadForm('approve.json');
            factory(Form::class)->create([
                'uid' => $definitions->getActivity('approve')->getProperty('formRef'),
                'content' => $this->loadForm('approve.json'),
                'title' => $json[0]->name,
                'content' => $json,
                'process_id' => $process->id,
            ]);

            $json = $this->loadForm('validate.json');
            factory(Form::class)->create([
                'uid' => $definitions->getActivity('validate')->getProperty('formRef'),
                'title' => $json[0]->name,
                'content' => $json,
                'process_id' => $process->id,
            ]);
            if ($definitions->findElementById('notavailable')) {
                $json = $this->loadForm('notavailable.json');
                factory(Form::class)->create([
                    'uid' => $definitions->getActivity('notavailable')->getProperty('formRef'),
                    'title' => $json[0]->name,
                    'content' => $json,
                    'process_id' => $process->id,
                ]);
            }

            echo 'Process created: ', $process->uid, "\n";
            
            //Create environment variables for the default processes
            factory(EnvironmentVariable::class)->create([
                'name' => 'hours_of_work',
                'description' => 'Regular schedule of hours of work for employees',
                'value' => '8'
            ]);
        }
    }

    /**
     * Load the JSON of a form.
     *
     * @param string $name
     *
     * @return object
     */
    private function loadForm($name)
    {
        return json_decode(file_get_contents(database_path('processes/forms/' . $name)));
    }

    private function languageOfMimeType($mime)
    {
        return in_array($mime, self::mimeTypes) ? array_search($mime, self::mimeTypes) : '';
    }
}
