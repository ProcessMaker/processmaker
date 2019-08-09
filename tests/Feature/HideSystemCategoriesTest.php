<?php
namespace Tests\Model;

use Tests\TestCase;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\Screen;
use Tests\Feature\Shared\RequestHelper;
use Illuminate\Support\Str;

class HideSystemCategoriesTest extends TestCase
{
    use RequestHelper;

    public function testCategoryFiltered() {
        $category = factory(ProcessCategory::class)->create([
            'is_system' => false,
        ]);
        $hiddenCategory = factory(ProcessCategory::class)->create([
            'is_system' => true,
        ]);
        $response = $this->apiCall('GET', route('api.process_categories.index'));
        $json = $response->json();
        $ids = array_map(function($d) { return $d['id']; }, $json['data']);

        $this->assertCount(1, $ids);
        $this->assertNotContains($hiddenCategory->id, $ids);
        $this->assertContains($category->id, $ids);
    }

    private function resourceInCategoryFiltered($model) {
        $prefix = strtolower(substr(strrchr($model, '\\'), 1));
        $category = factory(ProcessCategory::class)->create([
            'is_system' => false,
        ]);
        $instance = factory($model)->create([
            'process_category_id' => $category->id
        ]);
        $hiddenCategory = factory(ProcessCategory::class)->create([
            'is_system' => true,
        ]);
        $hiddenInstance = factory($model)->create([
            'process_category_id' => $hiddenCategory->id
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
            'process_category_id' => null
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

    private function resolveRouteBinding($model)
    {
        $prefix = strtolower(substr(strrchr($model, '\\'), 1));
        $hiddenCategory = factory(ProcessCategory::class)->create([
            'is_system' => true,
        ]);
        $hiddenInstance = factory($model)->create([
            'process_category_id' => $hiddenCategory->id
        ]);

        $response = $this->apiCall('GET', route('api.' . Str::plural($prefix) . '.show', [$hiddenInstance]));
        $response->assertStatus(404);

    }

    public function testResolveRouteBinding()
    {
        $this->resolveRouteBinding(Process::class);
        $this->resolveRouteBinding(Script::class);
        $this->resolveRouteBinding(Screen::class);
    }

}