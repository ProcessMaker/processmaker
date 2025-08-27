<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Managers\ModelerManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class RequestControllerShowTest extends TestCase
{
    use RequestHelper;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'is_administrator' => true,
        ]);
    }

    /**
     * Test that the show page renders managerModelerScripts correctly
     *
     * @return void
     */
    public function testShowPageRendersManagerModelerScripts()
    {
        // Create a process
        $process = Process::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Create a process request
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'user_id' => $this->user->id,
            'status' => 'ACTIVE',
        ]);

        // Create a task token
        ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'user_id' => $this->user->id,
            'element_type' => 'task',
            'status' => 'ACTIVE',
        ]);

        // Mock the ModelerManager to return test scripts
        $mockManager = $this->createMock(ModelerManager::class);
        $mockManager->method('getScriptWithParams')
            ->willReturn([
                [
                    'src' => '/js/test-script-1.js',
                    'type' => 'module',
                    'async' => true,
                ],
                [
                    'src' => '/js/test-script-2.js',
                    'type' => 'text/javascript',
                    'defer' => true,
                ],
                [
                    'src' => '/js/package-slideshow.js',
                    'type' => 'text/javascript',
                ],
                [
                    'src' => '/js/package-process-optimization.js',
                    'type' => 'text/javascript',
                ],
            ]);

        $this->app->instance(ModelerManager::class, $mockManager);

        // Make the request to the show page
        $response = $this->webCall('GET', '/requests/' . $request->id);

        // Assert the response is successful
        $response->assertStatus(200);
        $response->assertViewIs('requests.show');

        // Get the filtered scripts (should exclude the disabled ones)
        $managerModelerScripts = $response->viewData('managerModelerScripts');
        
        // Assert that the filtered scripts contain only the allowed scripts
        $this->assertCount(2, $managerModelerScripts);
        
        // Check that the disabled scripts are filtered out
        $scriptSources = array_column($managerModelerScripts, 'src');
        $this->assertContains('/js/test-script-1.js', $scriptSources);
        $this->assertContains('/js/test-script-2.js', $scriptSources);
        $this->assertNotContains('/js/package-slideshow.js', $scriptSources);
        $this->assertNotContains('/js/package-process-optimization.js', $scriptSources);

        // Assert that the scripts are rendered with the correct attributes in the HTML
        $response->assertSee('/js/test-script-1.js');
        $response->assertSee('/js/test-script-2.js');
        $response->assertSee('type="module"', false);
        $response->assertSee('async');
        $response->assertSee('defer');
        
        // Assert that disabled scripts are not rendered
        $response->assertDontSee('/js/package-slideshow.js');
        $response->assertDontSee('/js/package-process-optimization.js');
    }
}
