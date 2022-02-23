<?php
namespace Tests\Feature;

use Tests\TestCase;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;
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

        $response = $this->apiCall('GET', route('api.' . Str::plural($prefix) . '.index'));
        $json = $response->json();
        $ids = array_map(function($d) { return $d['id']; }, $json['data']);

        $this->assertCount(1, $ids);
        $this->assertNotContains($hiddenInstance->id, $ids);
        $this->assertContains($instance->id, $ids);
    }
    
    public function testResourceInCategoryFiltered() {
        $this->resourceInCategoryFiltered(Process::class);
        $this->resourceInCategoryFiltered(Script::class);
        $this->resourceInCategoryFiltered(Screen::class);
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

    public function testProcessRequestFiltered() {
        $category = factory(ProcessCategory::class)->create([
            'is_system' => false,
        ]);
        $instance = factory(Process::class)->create([
            'process_category_id' => $category->id
        ]);
        $hiddenCategory = factory(ProcessCategory::class)->create([
            'is_system' => true,
        ]);
        $hiddenInstance = factory(Process::class)->create([
            'process_category_id' => $hiddenCategory->id
        ]);
        $processRequest = factory(ProcessRequest::class)->create([
            'process_id' => $instance->id
        ]);
        $hiddenProcessRequest = factory(ProcessRequest::class)->create([
            'process_id' => $hiddenInstance->id
        ]);

        $response = $this->apiCall('GET', route('api.requests.index'));
        $json = $response->json();
        $ids = array_map(function($d) { return $d['id']; }, $json['data']);

        $this->assertCount(1, $ids);
        $this->assertNotContains($hiddenProcessRequest->id, $ids);
        $this->assertContains($processRequest->id, $ids);
    }

}