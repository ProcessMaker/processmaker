<?php

namespace Tests\Feature\Api;

use Exception;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\GlobalDataStore;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use Tests\Feature\Shared\ProcessTestingTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Test the process execution with requests
 *
 * @group process_tests
 */
class ProcessPatternsTest extends TestCase
{
    use RequestHelper;
    use ProcessTestingTrait;

    private $basePath = __DIR__ . '/bpmnPatterns/';

    /**
     * Make sure we have a personal access client set up
     *
     */
    public function setUpWithPersonalAccessClient()
    {
        $this->withPersonalAccessClient();
    }

    /**
     * Tests the bpmn process completing all active tasks
     *
     * @param string $bpmnFile
     *
     * @dataProvider prepareTestCasesProvider
     */
    public function testProcessPatterns($type, $bpmnFile, $context = [])
    {
        $this->$type($bpmnFile, $context);
    }

    /**
     * Prepare the test cases
     *
     * @return array
     */
    public function prepareTestCasesProvider()
    {
        $tests = [];
        $tests = $this->prepareTestCases('Conditional_StartEvent.bpmn', $tests);
        $tests = $this->prepareTestCases('Conditional_IntermediateEvent.bpmn', $tests);
        $tests = $this->prepareTestCases('MultiInstance_SequentialCallActivity.bpmn', $tests);
        return $tests;
    }

    /**
     * Tests the bpmn process completing all active tasks
     *
     * @param string $bpmnFile
     * @param array $tests
     *
     * @return array
     */
    private function prepareTestCases($bpmnFile, array $tests)
    {
        $file = "{$this->basePath}{$bpmnFile}";
        $jsonFile = substr($file, 0, -4) . 'json';
        if (file_exists($jsonFile)) {
            $contexts = json_decode(file_get_contents($jsonFile), true);
            foreach ($contexts as $context) {
                $tests[] = [
                    'runProcessWithContext',
                    $bpmnFile,
                    $context,
                ];
            }
        } else {
            $tests[] = [
                'runProcessWithoutContext',
                $bpmnFile,
            ];
        }
        return $tests;
    }

    /**
     * Run a process without json data
     *
     * @param string $bpmnFile
     *
     * @return void
     */
    private function runProcessWithoutContext($bpmnFile)
    {
        $bpmnRepository = new BpmnDocument();
        $bpmnRepository->load("{$this->basePath}{$bpmnFile}");
        $startEvents = $bpmnRepository->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'startEvent');
        foreach ($startEvents as $startEvent) {
            $data = [];
            $result = [];
            $this->runProcess($bpmnFile, $data, $startEvent->getAttribute('id'), $result, [], []);
        }
    }

    /**
     * Run a process with json data
     *
     * @param string $bpmnFile
     * @param array $context
     *
     * @return void
     */
    private function runProcessWithContext($bpmnFile, $context = [])
    {
        $events = isset($context['events']) ? $context['events'] : [];
        $output = isset($context['output']) ? $context['output'] : [];
        if (isset($context['requires'])) {
            foreach($context['requires'] as $index => $process) {
                $this->createProcess([
                    'id' => $index + 1,
                    'bpmn' => file_get_contents("{$this->basePath}{$process}"),
                ]);
            }
        }
        $this->runProcess($bpmnFile, $context['data'], $context['startEvent'], $context['result'], $events, $output);
    }

    /**
     * Run a process
     *
     * @param string $bpmnFile
     * @param array $data
     * @param string $startEvent
     * @param array $expectedResult
     * @param array $events
     *
     * @return void
     */
    private function runProcess($bpmnFile, $data = [], $startEvent, $expectedResult, $events, $output)
    {
        Cache::store('global_variables')->flush();
        $process = $this->createProcess(file_get_contents("{$this->basePath}{$bpmnFile}"));
        $definitions = $process->getDefinitions();
        $start = $definitions->getStartEvent($startEvent);
        if ($start->getEventDefinitions()->count() > 0) {
            $globalData = new GlobalDataStore();
            $globalData->setData($data);
            $this->artisan('schedule:run');
            $this->artisan('schedule:run');
        } else {
            $request = $this->startProcess($process, $startEvent, $data);
        }
        $pending = 1;
        while ($pending) {
            $submited = false;
            $token = ProcessRequestToken::where('status', 'ACTIVE')
                ->where('element_type', 'task')
                ->first();
            if ($token) {
                $submited = true;
                $this->completeTask($token, []);
            }
            // Trigger intermediate events
            if (!$submited) {
                $tokens = ProcessRequestToken::where('status', 'ACTIVE')
                    ->where('element_type', 'event')
                    ->get();
                foreach ($tokens as $token) {
                    $element = $token->getDefinition(true);
                    $nodeName = $element->getBpmnElement()->localName;
                    if ($nodeName === 'intermediateCatchEvent') {
                        foreach ($element->getEventDefinitions() as $event) {
                            switch ($event->getBpmnElement()->localName) {
                                case 'signalEventDefinition':
                                    WorkflowManager::throwSignalEventDefinition($event, $token);
                                    $submited = true;
                                    break;
                            }
                        }
                    }
                }
            }
            $pending = ProcessRequest::where('status', 'ACTIVE')
                ->count();
            if (!$submited && $pending) {
                $elements = implode(
                    ', ',
                    ProcessRequestToken::whereIn('status', ['ACTIVE', 'FAILING'])
                    ->pluck('element_name')
                    ->toArray()
                );
                // Get instance errors
                $errors = $this->getRequestsErrors();
                throw new Exception("The process got stuck in elements: {$elements}\n{$errors}");
            }
        }
        $tasks = ProcessRequestToken::
            whereIn('element_type', ['task', 'scriptTask', 'userTask', 'serviceTask'])
            ->get()
            ->pluck('element_id')
            ->toArray();
        // Get instance errors
        $errors = $this->getRequestsErrors();
        // Assertion: Check the process run as expected
        $this->assertEquals($expectedResult, $tasks, "FAILED: {$bpmnFile}\n{$errors}");
        if ($output) {
            $request->refresh();
            $this->assertData($output, $request->data);
        }
    }

    private function getRequestsErrors()
    {
        $errors = [];
        foreach (ProcessRequest::pluck('errors') as $error) {
            if ($error) {
                foreach ($error as $msg) {
                    $errors[] = $msg['message'];
                }
            }
        }
        return \implode("\n", $errors);
    }

    /**
     * Assert that $data contains the expected $subset
     *
     * @param mixed $subset
     * @param mixed $data
     * @param string $message
     * @param bool $skip
     *
     * @return mixed
     */
    private function assertData($subset, $data, $message = 'data', $skip = false)
    {
        if (!is_array($subset) || !is_array($data)) {
            if ($skip) {
                return $subset == $data;
            } else {
                return $this->assertEquals($subset, $data, $message . ' = ' . \json_encode($data) . ' does not match ' . \json_encode($subset));
            }
        }
        foreach ($subset as $key => $value) {
            if (substr($key, 0, 1) !== '*') {
                $this->assertData($value, $data[$key], "{$message}.{$key}");
                unset($subset[$key]);
                unset($data[$key]);
            }
        }
        foreach ($subset as $key => $value) {
            foreach ($data as $key1 => $value1) {
                if ($this->assertData($value, $value1, "{$message}.{$key}", true)) {
                    unset($subset[$key]);
                    unset($data[$key1]);
                    break;
                }
            }
        }
        if ($skip) {
            return count($subset) === 0;
        } else {
            $this->assertCount(0, $subset, "{$message} does not match");
        }
    }
}
