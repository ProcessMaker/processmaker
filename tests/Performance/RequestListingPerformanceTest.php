<?php

namespace Tests\Performance;

use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class RequestListingPerformanceTest extends TestCase
{
    use RequestHelper;

    public $withPermissions = true;

    public function testPerformance()
    {
        $seeder = new RequestListingPerformanceData();

        $seeder->requestCount = 10_000;
        $seeder->processCount = 10;
        $seeder->userCount = 1_000;

        $seeder->run();
        $seeder->associateWithUser(50, 50, 50);
        $this->user = $seeder->user;

        $route = route('api.requests.index');
        $start = microtime(true);
        $result = $this->apiCall('GET', $route);
        $duration = microtime(true) - $start;

        $total = $result->json()['meta']['total'];

        // $this->assertEquals(150, $total);
        // $this->assertLessThan(5, $duration);
        echo "\n----\nDuration: $duration, $total: $total\n----\n";
    }
}
