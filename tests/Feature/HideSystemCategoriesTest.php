<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\ScriptExecutor;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class HideSystemCategoriesTest extends TestCase
{
    use RequestHelper;

    private function categoryFiltered($model)
    {
        $prefix = strtolower(substr(strrchr($model, '\\'), 1));
        $categoryModel = $model . 'Category';
        $category = $categoryModel::factory()->create([
            'is_system' => false,
        ]);
        $hiddenCategory = $categoryModel::factory()->create([
            'is_system' => true,
        ]);
        $response = $this->apiCall('GET', route('api.' . $prefix . '_categories.index'));
        $json = $response->json();
        $ids = array_map(function ($d) {
            return $d['id'];
        }, $json['data']);

        $this->assertCount(1, $ids);
        $this->assertNotContains($hiddenCategory->id, $ids);
        $this->assertContains($category->id, $ids);
    }

    public function testCategoryFiltered()
    {
        $this->markTestSkipped('FOUR-6653');

        $this->categoryFiltered(Process::class);
        // $this->categoryFiltered(Script::class); // No api endpoint yet for script categories
        $this->categoryFiltered(Screen::class);
    }

    private function resourceInCategoryFiltered($model)
    {
        $prefix = strtolower(substr(strrchr($model, '\\'), 1));
        $categoryModel = $model . 'Category';
        $category = $categoryModel::factory()->create([
            'is_system' => false,
        ]);
        $instance = $model::factory()->create([
            $prefix . '_category_id' => $category->id,
        ]);
        $hiddenCategory = $categoryModel::factory()->create([
            'is_system' => true,
        ]);
        $hiddenInstance = $model::factory()->create([
            $prefix . '_category_id' => $hiddenCategory->id,
        ]);

        $response = $this->apiCall('GET', route('api.' . Str::plural($prefix) . '.index'));
        $json = $response->json();
        $ids = array_map(function ($d) {
            return $d['id'];
        }, $json['data']);

        $this->assertCount(1, $ids);
        $this->assertNotContains($hiddenInstance->id, $ids);
        $this->assertContains($instance->id, $ids);
    }

    public function testResourceInCategoryFiltered()
    {
        $this->resourceInCategoryFiltered(Process::class);
        $this->resourceInCategoryFiltered(Script::class);
        $this->resourceInCategoryFiltered(Screen::class);
    }

    private function resourceWithoutCategoryNotFiltered($model)
    {
        $prefix = strtolower(substr(strrchr($model, '\\'), 1));
        $instance = $model::factory()->create([
            $prefix . '_category_id' => null,
        ]);

        $response = $this->apiCall('GET', route('api.' . Str::plural($prefix) . '.index'));
        $json = $response->json();
        $ids = array_map(function ($d) {
            return $d['id'];
        }, $json['data']);

        $this->assertCount(1, $ids);
        $this->assertContains($instance->id, $ids);
    }

    public function testResourceWithoutCategoryNotFiltered()
    {
        $this->resourceWithoutCategoryNotFiltered(Process::class);
        $this->resourceWithoutCategoryNotFiltered(Script::class);
        $this->resourceWithoutCategoryNotFiltered(Screen::class);
    }

    public function testProcessRequestFiltered()
    {
        $category = ProcessCategory::factory()->create([
            'is_system' => false,
        ]);
        $instance = Process::factory()->create([
            'process_category_id' => $category->id,
        ]);
        $hiddenCategory = ProcessCategory::factory()->create([
            'is_system' => true,
        ]);
        $hiddenInstance = Process::factory()->create([
            'process_category_id' => $hiddenCategory->id,
        ]);
        $processRequest = ProcessRequest::factory()->create([
            'process_id' => $instance->id,
        ]);
        $hiddenProcessRequest = ProcessRequest::factory()->create([
            'process_id' => $hiddenInstance->id,
        ]);

        $response = $this->apiCall('GET', route('api.requests.index'));
        $json = $response->json();
        $ids = array_map(function ($d) {
            return $d['id'];
        }, $json['data']);

        $this->assertCount(1, $ids);
        $this->assertNotContains($hiddenProcessRequest->id, $ids);
        $this->assertContains($processRequest->id, $ids);
    }
}
