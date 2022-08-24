<?php

namespace Tests\Feature\Api;

use Database\Seeders\SignalSeeder;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\SignalData;

use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class SignalTest extends TestCase
{
    use RequestHelper;

    protected $resource = 'api.signals';

    protected $structure = [
        'id',
        'detail',
        'name',
        'processes',
        'type',
    ];

    public function createSignal($count = 1)
    {
        // Create global process for signals..
        Process::factory()->create(['name' => 'global_signals']);
        $faker = Faker::create();

        $signals = [];

        for ($i = 0; $i < $count; $i++) {
            // Create signal data ..
            $signalData = [
                'id' => $faker->unique()->lexify('????'),
                'name' => $faker->unique()->lexify('??????????????????'),
                'detail' => $faker->sentence(5),
            ];

            $newSignal = new SignalData(
                $signalData['id'],
                $signalData['name'],
                $signalData['detail'],
            );

            $errorValidations = SignalManager::validateSignal($newSignal, null);
            if (count($errorValidations) > 0) {
                return response(['errors' => $errorValidations], 422);
            }

            SignalManager::addSignal($newSignal, ['detail' => $signalData['detail']]);

            $signals[] = ['id' => $newSignal->getId(), 'name' => $newSignal->getName()];
        }

        return $signals;
    }

    /**
     * Get a list of Signals on first page.
     */
    public function testListSignalOnFirstPage()
    {
        // Create some signals
        $countSignals = 20;
        $signals = $this->createSignal($countSignals);

        //Get a page of signals
        $page = 1;
        $perPage = 10;

        $route = route($this->resource . '.index');
        $response = $this->apiCall('GET', $route . '?page=' . $page . '&per_page=' . $perPage);
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => [$this->structure]]);
        $data = $response->json('data');
        $meta = $response->json('meta');
        // Verify the meta values
        $this->assertArraySubset([
            'total' => $countSignals,
            'count' => $perPage,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => 2,
        ], $meta);
        //Verify the data size
        $this->assertCount($meta['count'], $data);
    }

    /**
     * Get a list of Signals on second page.
     */
    public function testListSignalOnSecondPage()
    {
        // Create some signals
        $countSignals = 20;
        $signals = $this->createSignal($countSignals);

        //Get a page of signals
        $page = 2;
        $perPage = 10;

        $route = route($this->resource . '.index');
        $response = $this->apiCall('GET', $route . '?page=' . $page . '&per_page=' . $perPage);
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => [$this->structure]]);
        $data = $response->json('data');
        $meta = $response->json('meta');
        // Verify the meta values
        $this->assertArraySubset([
            'total' => $countSignals,
            'count' => $perPage,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => 2,
        ], $meta);
        //Verify the data size
        $this->assertCount($meta['count'], $data);
    }

    /**
     * Get a list of Signals first page with ten records should return one total_pages.
     */
    public function testListSignalOnPageWithTenRecordsShouldReturnOneTotalPages()
    {
        // Create some signals
        $countSignals = 10;
        $signals = $this->createSignal($countSignals);

        //Get a page of signals
        $page = 1;
        $perPage = 10;

        $route = route($this->resource . '.index');
        $response = $this->apiCall('GET', $route . '?page=' . $page . '&per_page=' . $perPage);
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => [$this->structure]]);
        $data = $response->json('data');
        $meta = $response->json('meta');
        // Verify the meta values
        $this->assertArraySubset([
            'total' => $countSignals,
            'count' => $perPage,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => 1,
        ], $meta);
        //Verify the data size
        $this->assertCount($meta['count'], $data);
    }

    /**
     * Delete signal that is not present in a process or a system process.
     */
    public function testDeleteSignal()
    {
        // Create signal
        $signal = $this->createSignal()[0];

        // Assert signal was created
        $signalFound = SignalManager::findSignal($signal['id']);
        $this->assertEquals($signal['id'], $signalFound->getId());

        $route = route($this->resource . '.destroy', $signal['id']);
        $response = $this->apiCall('DELETE', $route);

        // Assert response 201
        $response->assertStatus(201);

        // Assert signal was deleted
        $signalFound = SignalManager::findSignal($signal['id']);
        $this->assertNull($signalFound);
    }

    /**
     * Delete signal that is present in a process or a system process should return a error.
     */
    public function testDeleteSignalPresentInProcessShouldNotBeDeleted()
    {
        // Create signal
        $signal = $this->createSignal()[0];

        // Create process with the signal assigned
        $bpmnContent = file_get_contents(__DIR__ . '/processes/SignalSimple.bpmn');
        $process = Process::factory()->create([
            'bpmn' => str_replace(['signalRef="MySignalID"', 'id="MySignalID"'], ['signalRef="' . $signal['id'] . '"', 'id="' . $signal['id'] . '"'], $bpmnContent),
        ]);

        // Assert signal was created
        $signalFound = SignalManager::findSignal($signal['id']);
        $this->assertEquals($signal['id'], $signalFound->getId());

        $route = route($this->resource . '.destroy', $signal['id']);
        $response = $this->apiCall('DELETE', $route);

        // Assert response 403
        $response->assertStatus(403);

        // Assert response has the correct error message
        $this->assertEquals(
            $response->json()['message'],
            __('Signals present in processes and system processes cannot be deleted.')
        );

        // Assert signal was deleted
        $signalFound = SignalManager::findSignal($signal['id']);
        $this->assertEquals($signal['id'], $signalFound->getId());
    }

    /**
     * Update signal that is not a system signal.
     */
    public function testUpdateNotSystemSignal()
    {
        // Create signal
        $signal = $this->createSignal()[0];

        // Assert signal was created
        $signalFound = SignalManager::findSignal($signal['id']);
        $this->assertEquals($signal['id'], $signalFound->getId());

        $data = [
            'name' => $signal['name'] . '-MODIFIED',
            'id' => $signal['id'] . '-MODIFIED',
        ];
        $route = route($this->resource . '.update', [$signal['id']]);
        $response = $this->apiCall('PUT', $route, $data);

        // Assert response 200
        $response->assertStatus(200);

        // Assert signal was updated
        $signalFound = SignalManager::findSignal($data['id']);
        $this->assertEquals($signalFound->getName(), $data['name']);
        $this->assertEquals($signalFound->getId(), $data['id']);
    }

    /**
     * Update signal that is a system signal should not be modified.
     */
    public function testUpdateSystemSignalShouldNotBeModified()
    {
        // Create signal
        $signal = $this->createSignal()[0];

        //Create a system category
        $systemProcessCategory = ProcessCategory::factory()->create(['is_system' => true]);

        // Create process with the signal assigned
        $bpmnContent = file_get_contents(__DIR__ . '/processes/SignalSimple.bpmn');
        $process = Process::factory()->create([
            'bpmn' => str_replace(['signalRef="MySignalID"', 'id="MySignalID"'], ['signalRef="' . $signal['id'] . '"', 'id="' . $signal['id'] . '"'], $bpmnContent),
            'process_category_id' => $systemProcessCategory,
        ]);

        // Assert signal was created
        $signalFound = SignalManager::findSignal($signal['id']);
        $this->assertEquals($signal['id'], $signalFound->getId());

        $data = [
            'name' => $signal['name'] . '-MODIFIED',
            'id' => $signal['id'] . '-MODIFIED',
        ];
        $route = route($this->resource . '.update', [$signal['id']]);
        $response = $this->apiCall('PUT', $route, $data);

        // Assert response 403
        $response->assertStatus(403);

        // Assert response has the correct error message
        $this->assertEquals(
            $response->json()['message'],
            __('System signals cannot be modified.')
        );

        // Assert signal was deleted
        $signalFound = SignalManager::findSignal($signal['id']);
        $this->assertEquals($signal['id'], $signalFound->getId());
    }
}
