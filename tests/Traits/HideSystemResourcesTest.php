<?php
namespace Tests\Model;

use Tests\TestCase;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use Tests\Feature\Shared\RequestHelper;
use Illuminate\Support\Str;

class HideSystemCategoriesTest extends TestCase
{
    use RequestHelper;

    private function categoryFiltered($model) {
        $prefix = strtolower(substr(strrchr($model, '\\'), 1));
        $category = factory($model . 'Category')->create([
            'is_system' => false,
        ]);
        $hiddenCategory = factory($model . 'Category')->create([
            'is_system' => true,
        ]);
        $response = $this->apiCall('GET', route('api.' . $prefix . '_categories.index'));
        $json = $response->json();
        $ids = array_map(function($d) { return $d['id']; }, $json['data']);

        $this->assertCount(1, $ids);
        $this->assertNotContains($hiddenCategory->id, $ids);
        $this->assertContains($category->id, $ids);
    }

    public function testCategoryFiltered() {
        $this->categoryFiltered(Process::class);
        // $this->categoryFiltered(Script::class); // No api endpoint yet for script categories
        $this->categoryFiltered(Screen::class);
    }
    
    private function resourceInCategoryFiltered($model) {
        $prefix = strtolower(substr(strrchr($model, '\\'), 1));
        $category = factory($model . 'Category')->create([
            'is_system' => false,
        ]);
        $instance = factory($model)->create([
            $prefix . '_category_id' => $category->id
        ]);
        $hiddenCategory = factory($model . 'Category')->create([
            'is_system' => true,
        ]);
        $hiddenInstance = factory($model)->create([
            $prefix . '_category_id' => $hiddenCategory->id
        ]);
        $noCategoryInstance = factory($model)->create([
            $prefix . '_category_id' => null
        ]);

        $response = $this->apiCall('GET', route('api.' . Str::plural($prefix) . '.index'));
        $json = $response->json();
        $ids = array_map(function($d) { return $d['id']; }, $json['data']);

        $this->assertCount(2, $ids);
        $this->assertNotContains($hiddenInstance->id, $ids);
        $this->assertContains($instance->id, $ids);
        
        $response = $this->apiCall('GET', route('api.' . Str::plural($prefix) . '.show', $instance->id));
        $response->assertStatus(200);
        
        $response = $this->apiCall('GET', route('api.' . Str::plural($prefix) . '.show', $hiddenInstance->id));
        $response->assertStatus(404);
        
        $response = $this->apiCall('GET', route('api.' . Str::plural($prefix) . '.show', $noCategoryInstance->id));
        $response->assertStatus(200);
    }
    
    public function testResourceInCategoryFiltered() {
        $this->resourceInCategoryFiltered(Process::class);
        $this->resourceInCategoryFiltered(Script::class);
        $this->resourceInCategoryFiltered(Screen::class);
    }

    public function testRequestAndTasksInCategoryFiltered() {
        $category = factory(ProcessCategory::class)->create([
            'is_system' => false,
        ]);
        $process = factory(Process::class)->create([
            'process_category_id' => $category->id
        ]);
        $request = factory(ProcessRequest::class)->create([
            'process_id' => $process->id
        ]);
        $task = factory(ProcessRequestToken::class)->create([
            'process_id' => $process->id,
            'process_request_id' => $request->id
        ]);

        $hiddenCategory = factory(ProcessCategory::class)->create([
            'is_system' => true,
        ]);
        $hiddenProcess = factory(Process::class)->create([
            'process_category_id' => $hiddenCategory->id
        ]);
        $hiddenRequest = factory(ProcessRequest::class)->create([
            'process_id' => $hiddenProcess->id
        ]);
        $hiddenTask = factory(ProcessRequestToken::class)->create([
            'process_id' => $hiddenProcess->id,
            'process_request_id' => $hiddenRequest->id
        ]);
        
        $noCategoryProcess = factory(Process::class)->create([
            'process_category_id' => null
        ]);
        $noCategoryRequest = factory(ProcessRequest::class)->create([
            'process_id' => $noCategoryProcess->id
        ]);
        $noCategoryTask = factory(ProcessRequestToken::class)->create([
            'process_id' => $noCategoryProcess->id,
            'process_request_id' => $noCategoryRequest->id
        ]);

        // Check Requests
        $response = $this->apiCall('GET', route('api.requests.index'));
        $json = $response->json();
        $ids = array_map(function($d) { return $d['id']; }, $json['data']);

        $this->assertCount(2, $ids);
        $this->assertNotContains($hiddenRequest->id, $ids);
        $this->assertContains($request->id, $ids);
        $this->assertContains($noCategoryRequest->id, $ids);
        
        $response = $this->apiCall('GET', route('api.requests.show', $request->id));
        $response->assertStatus(200);
        
        $response = $this->apiCall('GET', route('api.requests.show', $hiddenRequest->id));
        $response->assertStatus(404);
        
        $response = $this->apiCall('GET', route('api.requests.show', $noCategoryRequest->id));
        $response->assertStatus(200);

        // Check Tasks
        $response = $this->apiCall('GET', route('api.tasks.index'));
        $json = $response->json();
        $ids = array_map(function($d) { return $d['id']; }, $json['data']);

        $this->assertCount(2, $ids);
        $this->assertNotContains($hiddenTask->id, $ids);
        $this->assertContains($task->id, $ids);
        $this->assertContains($noCategoryTask->id, $ids);
        
        $response = $this->apiCall('GET', route('api.tasks.show', $task->id));
        $response->assertStatus(200);
        
        $response = $this->apiCall('GET', route('api.tasks.show', $hiddenTask->id));
        $response->assertStatus(404);
        
        $response = $this->apiCall('GET', route('api.tasks.show', $noCategoryTask->id));
        $response->assertStatus(200);
    }
    
    private function resourceWithoutCategoryNotFiltered($model) {
        $prefix = strtolower(substr(strrchr($model, '\\'), 1));
        $instance = factory($model)->create([
            $prefix . '_category_id' => null
        ]);
        
        $response = $this->apiCall('GET', route('api.' . Str::plural($prefix) . '.index'));
        $json = $response->json();
        $ids = array_map(function($d) { return $d['id']; }, $json['data']);

        $this->assertCount(1, $ids);
        $this->assertContains($instance->id, $ids);
    }
    
    public function testResourceWithoutCategoryNotFiltered() {
        $this->resourceWithoutCategoryNotFiltered(Process::class);
        $this->resourceWithoutCategoryNotFiltered(Script::class);
        $this->resourceWithoutCategoryNotFiltered(Screen::class);
    }

}