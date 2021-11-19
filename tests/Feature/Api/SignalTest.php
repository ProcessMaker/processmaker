<?php

namespace Tests\Feature\Api;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\SignalData;
use SignalSeeder;
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
        'type'
    ];

    public function createSignal($count)
    {
        // Create global process for signals..
        factory(Process::class)->create(['name' => 'global_signals']);
        $faker = Faker::create();

        $signals = [];

        for ($i=0; $i < $count; $i++) {
            // Create signal data ..
            $signalData = [
                'id' => $faker->unique()->lexify('????'),
                'name' => $faker->unique()->lexify('??????????????????'),
                'detail' => $faker->sentence(5)
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
        ], $meta);
        //Verify the data size
        $this->assertCount($meta['count'], $data);
    }
}
