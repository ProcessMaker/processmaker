<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;

/**
 * Tests routes related to processes / CRUD related methods
 *
 * @group process_tests
 */
class SearchByCategoryTest extends TestCase
{
    use WithFaker;
    use ResourceAssertionsTrait;
    use RequestHelper;

    /**
     * Test filtering processes by category
     */
    public function testSearchProcessesByCategory()
    {
        $cata = factory(ProcessCategory::class)->create(['name' => 'category_a']);
        $catb = factory(ProcessCategory::class)->create(['name' => 'category_b']);
        $catc = factory(ProcessCategory::class)->create(['name' => 'category_c']);

        $entity1 = factory(Process::class)->create();
        $entity2 = factory(Process::class)->create();
        $entity3 = factory(Process::class)->create();
        $entity4 = factory(Process::class)->create();

        $entity1->categories()->attach($cata);
        $entity1->categories()->attach($catb);

        $entity2->categories()->attach($cata);

        $entity3->categories()->attach($catb);
        $entity3->categories()->attach($catc);

        $entity4->categories()->attach($cata);
        $entity4->categories()->attach($catb);
        $entity4->categories()->attach($catc);

        $route = route('api.processes.index');
        // check that the returned list and metadata shows all the processes
        $response = $this->apiCall('GET', $route.'?filter=ACTIVE&per_page=10');
        $data = $response->json('data');
        $meta = $response->json('meta');
        $this->assertCount(4, $data);
        $this->assertEquals(4, $meta['count']);

        // check that the returned list and metadata shows all the processes with category_a
        $response = $this->apiCall('GET', $route.'?filter=category_a&per_page=10');
        $data = $response->json('data');
        $dataIds = array_map(function ($item) {
            return $item['id'];
        }, $data);
        $meta = $response->json('meta');
        $this->assertCount(3, $data);
        $this->assertEquals(3, $meta['count']);
        $this->assertEqualsCanonicalizing([$entity1->id, $entity2->id, $entity4->id], $dataIds);

        // check that the returned list and metadata shows all the processes with category_c
        $response = $this->apiCall('GET', $route.'?filter=category_c&per_page=10');
        $data = $response->json('data');
        $dataIds = array_map(function ($item) {
            return $item['id'];
        }, $data);
        $meta = $response->json('meta');
        $this->assertCount(2, $data);
        $this->assertEquals(2, $meta['count']);
        $this->assertEqualsCanonicalizing([$entity3->id, $entity4->id], $dataIds);

        // check that the returned list and metadata is empty with filter by zyx
        $response = $this->apiCall('GET', $route.'?filter=zyx&per_page=10');
        $data = $response->json('data');
        $dataIds = array_map(function ($item) {
            return $item['id'];
        }, $data);
        $meta = $response->json('meta');
        $this->assertCount(0, $data);
        $this->assertEquals(0, $meta['count']);
    }

    /**
     * Test filtering processes by category
     */
    public function testSearchScreensByCategory()
    {
        $cata = factory(ScreenCategory::class)->create(['name' => 'category_a']);
        $catb = factory(ScreenCategory::class)->create(['name' => 'category_b']);
        $catc = factory(ScreenCategory::class)->create(['name' => 'category_c']);

        $entity1 = factory(Screen::class)->create();
        $entity2 = factory(Screen::class)->create();
        $entity3 = factory(Screen::class)->create();
        $entity4 = factory(Screen::class)->create();

        $entity1->categories()->attach($cata);
        $entity1->categories()->attach($catb);

        $entity2->categories()->attach($cata);

        $entity3->categories()->attach($catb);
        $entity3->categories()->attach($catc);

        $entity4->categories()->attach($cata);
        $entity4->categories()->attach($catb);
        $entity4->categories()->attach($catc);

        $route = route('api.screens.index');
        // check that the returned list and metadata shows all the processes
        $response = $this->apiCall('GET', $route.'?per_page=10');
        $data = $response->json('data');
        $meta = $response->json('meta');
        $this->assertCount(4, $data);
        $this->assertEquals(4, $meta['count']);

        // check that the returned list and metadata shows all the processes with category_a
        $response = $this->apiCall('GET', $route.'?filter=category_a&per_page=10');
        $data = $response->json('data');
        $dataIds = array_map(function ($item) {
            return $item['id'];
        }, $data);
        $meta = $response->json('meta');
        $this->assertCount(3, $data);
        $this->assertEquals(3, $meta['count']);
        $this->assertEqualsCanonicalizing([$entity1->id, $entity2->id, $entity4->id], $dataIds);

        // check that the returned list and metadata shows all the processes with category_c
        $response = $this->apiCall('GET', $route.'?filter=category_c&per_page=10');
        $data = $response->json('data');
        $dataIds = array_map(function ($item) {
            return $item['id'];
        }, $data);
        $meta = $response->json('meta');
        $this->assertCount(2, $data);
        $this->assertEquals(2, $meta['count']);
        $this->assertEqualsCanonicalizing([$entity3->id, $entity4->id], $dataIds);

        // check that the returned list and metadata is empty with filter by zyx
        $response = $this->apiCall('GET', $route.'?filter=zyx&per_page=10');
        $data = $response->json('data');
        $dataIds = array_map(function ($item) {
            return $item['id'];
        }, $data);
        $meta = $response->json('meta');
        $this->assertCount(0, $data);
        $this->assertEquals(0, $meta['count']);
    }

    /**
     * Test filtering processes by category
     */
    public function testSearchScriptsByCategory()
    {
        $cata = factory(ScriptCategory::class)->create(['name' => 'category_a']);
        $catb = factory(ScriptCategory::class)->create(['name' => 'category_b']);
        $catc = factory(ScriptCategory::class)->create(['name' => 'category_c']);

        $entity1 = factory(Script::class)->create();
        $entity2 = factory(Script::class)->create();
        $entity3 = factory(Script::class)->create();
        $entity4 = factory(Script::class)->create();

        $entity1->categories()->attach($cata);
        $entity1->categories()->attach($catb);

        $entity2->categories()->attach($cata);

        $entity3->categories()->attach($catb);
        $entity3->categories()->attach($catc);

        $entity4->categories()->attach($cata);
        $entity4->categories()->attach($catb);
        $entity4->categories()->attach($catc);

        $route = route('api.scripts.index');
        // check that the returned list and metadata shows all the processes
        $response = $this->apiCall('GET', $route.'?per_page=10');
        $data = $response->json('data');
        $meta = $response->json('meta');
        $this->assertCount(4, $data);
        $this->assertEquals(4, $meta['count']);

        // check that the returned list and metadata shows all the processes with category_a
        $response = $this->apiCall('GET', $route.'?filter=category_a&per_page=10');
        $data = $response->json('data');
        $dataIds = array_map(function ($item) {
            return $item['id'];
        }, $data);
        $meta = $response->json('meta');
        $this->assertCount(3, $data);
        $this->assertEquals(3, $meta['count']);
        $this->assertEqualsCanonicalizing([$entity1->id, $entity2->id, $entity4->id], $dataIds);

        // check that the returned list and metadata shows all the processes with category_c
        $response = $this->apiCall('GET', $route.'?filter=category_c&per_page=10');
        $data = $response->json('data');
        $dataIds = array_map(function ($item) {
            return $item['id'];
        }, $data);
        $meta = $response->json('meta');
        $this->assertCount(2, $data);
        $this->assertEquals(2, $meta['count']);
        $this->assertEqualsCanonicalizing([$entity3->id, $entity4->id], $dataIds);

        // check that the returned list and metadata is empty with filter by zyx
        $response = $this->apiCall('GET', $route.'?filter=zyx&per_page=10');
        $data = $response->json('data');
        $dataIds = array_map(function ($item) {
            return $item['id'];
        }, $data);
        $meta = $response->json('meta');
        $this->assertCount(0, $data);
        $this->assertEquals(0, $meta['count']);
    }
}
