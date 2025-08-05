<?php

namespace Tests\Unit\ProcessMaker\Nayra\Services;

use Exception;
use ProcessMaker\Nayra\Services\FixBpmnSchemaService;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use Tests\TestCase;

/**
 * Class FixBpmnSchemaServiceTest
 */
class FixBpmnSchemaServiceTest extends TestCase
{
    /**
     * Test with BPMN definition created in another modeler,
     * an Exception should be throwed
     *
     * @return void
     */
    public function testExceptionInIncompleteProcess()
    {
        $bpmn = file_get_contents(
            __DIR__ .
                "/../../../../Fixtures/process_data_input_without_targetref.bpmn"
        );

        $document = new BpmnDocument();
        $document->loadXML($bpmn);

        $this->expectException(Exception::class);
        $validation = $document->validateBPMNSchema(
            public_path("definitions/ProcessMaker.xsd")
        );
    }

    /**
     * Test fixing incomplete BPMN definition created in another modeler
     *
     * @return void
     */
    public function testFixIncompleteProcess()
    {
        $bpmn = file_get_contents(
            __DIR__ .
                "/../../../../Fixtures/process_data_input_without_targetref.bpmn"
        );

        $fixBpmnSchemaService = app(FixBpmnSchemaService::class);
        $bpmn = $fixBpmnSchemaService->fix($bpmn);

        $document = new BpmnDocument();
        $document->loadXML($bpmn);
        $validation = $document->validateBPMNSchema(
            public_path("definitions/ProcessMaker.xsd")
        );

        $this->assertTrue($validation);
    }

    /**
     * Test using a BPMN definition created in PM4
     *
     * @return void
     */
    public function testFixPm4Process()
    {
        $bpmn = file_get_contents(
            __DIR__ .
                "/../../../../Fixtures/process_data_input_generated_in_pm4.bpmn"
        );

        $fixBpmnSchemaService = app(FixBpmnSchemaService::class);
        $bpmn = $fixBpmnSchemaService->fix($bpmn);

        $document = new BpmnDocument();
        $document->loadXML($bpmn);
        $validation = $document->validateBPMNSchema(
            public_path("definitions/ProcessMaker.xsd")
        );

        $this->assertTrue($validation);
    }
}
