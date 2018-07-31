<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Form;

class ProcessSeeder extends Seeder
{

    private $approvalFormUid = '200f95eb-76d8-459d-b56a-ea605bea4e3f';
    private $hrFormUid = '300f95eb-76d8-459d-b56a-ea605bea4e3f';
    private $requestFormUid = '100f95eb-76d8-459d-b56a-ea605bea4e3f';
    private $requestForm = [
        [
            "type" => "FormInput",
            "field" => "label",
            "config" => [
                "label" => "Text Label",
                "helper" => "The text to display",
            ]
        ],
        [
            "type" => "FormSelect",
            "field" => "fontWeight",
            "config" => [
                "label" => "Font Weight",
                "helper" => "The weight of the text",
                "options" => [
                    [
                        "value" => 'normal',
                        "content" => 'Normal'
                    ],
                    [
                        "value" => 'bold',
                        "content" => 'Bold'
                    ]
                ]
            ]
        ],
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
            
            $definitions = $process->getDefinitions();

            //Add forms to the process
            factory(Form::class)->create([
                'uid' => $definitions->getActivity('request')->getProperty('formRef'),
                'content' => $this->requestForm,
                'process_id' => $process->id,
            ]);

            factory(Form::class)->create([
                'uid' => $definitions->getActivity('approve')->getProperty('formRef'),
                'content' => $this->requestForm,
                'process_id' => $process->id,
            ]);

            $form = factory(Form::class)->create([
                'uid' => $definitions->getActivity('validate')->getProperty('formRef'),
                'content' => $this->requestForm,
                'process_id' => $process->id,
            ]);

            echo 'Process created: ', $process->uid, "\n";
        }
    }
}
