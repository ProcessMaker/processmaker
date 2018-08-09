<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Form;

class ProcessSeeder extends Seeder
{

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

            echo 'Process created: ', $process->uid, "\n";
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
}
