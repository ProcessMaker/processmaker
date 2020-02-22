<?php
namespace Tests\Feature\Api;

use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ScriptCategoriesTest extends TestCase
{
    use RequestHelper;

    protected function setUpStoragePath() {
        \App::useStoragePath('/tmp');
        if (!is_dir(storage_path('docker-build-config'))) {
            mkdir(storage_path('docker-build-config'), 0755, true);
        }
    }

    public function testGetScriptExecutors()
    {
        file_put_contents('/tmp/docker-build-config/Dockerfile-php', 'Foo');

        $result = $this->apiCall('GET', route('api.script-executors.index'));
        $result->assertStatus(200);

        $result = $result->json();
        $this->assertArrayHasKey('php', $result['languages']);

        $docker = $result['languages']['php']['userDockerfileContents'];
        $this->assertEquals("Foo", $docker);
    }

    public function testSetDockerfile()
    {
        $route = route('api.script-executors.update', ['language' => 'lua']);
        $result = $this->apiCall('PUT', $route, ['userDockerfileContents' => 'Bar']);
        
        $result = $this->apiCall('GET', route('api.script-executors.index'));
        $result = $result->json();
        $docker = $result['languages']['lua']['userDockerfileContents'];
        $this->assertEquals('Bar', $docker);
    }
}
