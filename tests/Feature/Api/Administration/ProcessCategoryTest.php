<?php

namespace Tests\Feature\Api\Administration;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessCategory;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\TestCase;

class ProcessCategoryTest extends TestCase
{
    use DatabaseTransactions;

    const API_TEST_CATEGORY = '/api/1.0/category/';
    const API_TEST_CATEGORIES = '/api/1.0/categories';
    
    private $testUserRole = Role::PROCESSMAKER_ADMIN;
    private $testUsers = [];

    /**
     * Create an api that authenticates with the test users
     */
    private function api()
    {
        if (!array_key_exists($this->testUserRole, $this->testUsers)) {
            $this->testUsers[$this->testUserRole] = factory(User::class)->create([
                'password' => Hash::make('password'),
                'role_id' => Role::where('code', $this->testUserRole)->first()->id,
            ]);
        }
        $user = $this->testUsers[$this->testUserRole];
        return call_user_func_array(
            [$this->actingAs($user, 'api'), 'json'], func_get_args()
        );
    }

    /**
     * Test access control for the process category endpoints.
     */
    public function testAccessControl()
    {
        $this->testUserRole = Role::PROCESSMAKER_OPERATOR;

        $catUid = factory(ProcessCategory::class)->create()->uid;

        $response = $this->api('GET', self::API_TEST_CATEGORIES);

        $response->assertStatus(403);

        $response = $this->api('POST', self::API_TEST_CATEGORY, []);
        $response->assertStatus(403);

        $response = $this->api('GET', self::API_TEST_CATEGORY . $catUid);
        $response->assertStatus(403);

        $response = $this->api('PUT', self::API_TEST_CATEGORY . $catUid, []);
        $response->assertStatus(403);

        $response = $this->api('DELETE', self::API_TEST_CATEGORY . $catUid);
        $response->assertStatus(403);
    }

    /**
     * Test get the list of categories.
     */
    public function testGetListOfCategories()
    {
        //Create test categories
        $process = factory(Process::class)->create();
        $processCategory = $process->category;

        $response = $this->api('GET', self::API_TEST_CATEGORIES);
        $response->assertStatus(200);
        $response->assertJsonStructure();
        $response->assertJsonFragment(
            [
                "uid"             => $processCategory->uid,
                "name"            => $processCategory->name,
                "status"          => $processCategory->status,
                "processes_count" => 1,
            ]
        );
    }

    /**
     * Test get the list of active categories.
     */
    public function testGetListOfCategoriesActive()
    {
        // set seeded categories to inactive
        ProcessCategory::query()->update(
            ['status' => ProcessCategory::STATUS_INACTIVE]
        );

        //Create test categories
        $processCategory1 = factory(ProcessCategory::class)->create(
            ['status' => ProcessCategory::STATUS_INACTIVE]
        );
        $processCategory2 = factory(ProcessCategory::class)->create(
            ['status' => ProcessCategory::STATUS_ACTIVE]
        );

        $response = $this->api('GET', self::API_TEST_CATEGORIES . '?status=ACTIVE');
        $response->assertStatus(200);
        $response->assertJsonStructure();
        $this->assertCount(1, $response->json()['data']);
        $response->assertJsonFragment(
            [
                "uid"             => $processCategory2->uid,
            ]
        );
    }

    /**
     * Test get the list of categories filter
     */
    public function testGetListOfCategoriesFilter()
    {
        $process = factory(Process::class)->create();
        
        //Create test categories
        $processCategory = $process->category;
        $otherCategory = factory(ProcessCategory::class)->create();

        //Test filter
        $response = $this->api('GET', self::API_TEST_CATEGORIES . '?filter=' . urlencode($processCategory->name));
        $response->assertStatus(200);
        $response->assertJsonStructure();

        $response->assertJsonFragment(
            [
                "uid"             => $processCategory->uid,
                "name"            => $processCategory->name,
                "status"          => $processCategory->status,
                "processes_count" => 1,
            ]
        );

    }
    /**
     * Test sorting by category name
     */
    public function testGetListOfCategoriesSorted()
    {
        factory(ProcessCategory::class)->create([
            'name' => 'first test category'
        ]);
        
        factory(ProcessCategory::class)->create([
            'name' => 'second test category'
        ]);
        
        // defaults sort to name ASC
        $response = $this->api('GET', self::API_TEST_CATEGORIES . '?filter=test');
        $json = $response->json()['data'];
        $this->assertEquals($json[0]['name'], 'first test category');
        $this->assertEquals($json[1]['name'], 'second test category');
        
        // sort by name ASC
        $response = $this->api(
            'GET', self::API_TEST_CATEGORIES . '?filter=test&order_by=name&order_direction=ASC'
        );
        $json = $response->json()['data'];
        $this->assertEquals($json[0]['name'], 'first test category');
        $this->assertEquals($json[1]['name'], 'second test category');
        
        // sort by name DESC
        $response = $this->api(
            'GET', self::API_TEST_CATEGORIES . '?filter=test&order_by=name&order_direction=DESC'
        );
        $json = $response->json()['data'];
        $this->assertEquals($json[0]['name'], 'second test category');
        $this->assertEquals($json[1]['name'], 'first test category');
    }

    /**
     * Test get the list of categories without results
     */
    public function testGetFilterWithoutResult()
    {
        //Create test categories
        $processCategory = factory(ProcessCategory::class)->create();
        factory(ProcessCategory::class)->create();
        factory(Process::class)->create([
            'process_category_id' => $processCategory->id
        ]);
        //Test filter not found
        $response = $this->api('GET', self::API_TEST_CATEGORIES . '?filter=NOT_FOUND_TEXT');
        $response->assertStatus(200);
        $response->assertJsonStructure();
        $this->assertCount(0, $response->json()['data']);
    }

    /**
     * Test get the list of categories with start and limit
     */
    public function testGetListOfCategoriesStartLimit()
    {
        //Create test categories
        $processCategory1 = factory(ProcessCategory::class)->create(['name' => "a"]);
        $processCategory2 = factory(ProcessCategory::class)->create(['name' => "b"]);

        //Test start and limit
        $response = $this->api('GET', self::API_TEST_CATEGORIES . '?per_page=1');
        $response->assertStatus(200);
        $response->assertJsonStructure();
        $response->assertJsonFragment(
            [
                "uid"             => $processCategory1->uid,
                "name"            => $processCategory1->name,
                "processes_count" => 0,
            ]
        );
        $this->assertCount(1, $response->json()['data']);
    }

    /**
     * Test the creation of process categories.
     */
    public function testCreateProcessCategory()
    {
        $faker = Faker::create();

        $processCategory = factory(ProcessCategory::class)->make();
        $data = [
            "name" => $processCategory->name,
            "status" => $processCategory->status,
        ];
        $response = $this->api('POST', self::API_TEST_CATEGORY, $data);
        $response->assertStatus(201);
        $response->assertJsonStructure();
        $processCategoryJson = $response->json();
        $processCategory = ProcessCategory::where('uid', $processCategoryJson['uid'])
            ->first();
        $this->assertNotNull($processCategory);
        $response->assertJsonFragment(
            [
                "uid" => $processCategory->uid,
                "name" => $processCategory->name,
                "status" => $processCategory->status,
                "processes_count" => 0,
            ]
        );

        //Validate required name
        $response = $this->api('POST', self::API_TEST_CATEGORY, []);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.required', ['attribute' => 'name']),
            $response->json()['errors']['name'][0]
        );

        //Validate creation of duplicated category
        $response = $this->api('POST', self::API_TEST_CATEGORY, $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.unique', ['attribute' => 'name']),
            $response->json()['errors']['name'][0]
        );

        //Validate invalid large name
        $data = [
            "name" => $faker->sentence(100),
            "status" => $processCategory->status,
        ];
        $response = $this->api('POST', self::API_TEST_CATEGORY, $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.max.string', ['attribute' => 'name', 'max' => 100]),
            $response->json()['errors']['name'][0]
        );
    }

    /**
     * Test the update of process categories.
     */
    public function testUpdateProcessCategory()
    {
        $faker = Faker::create();

        $processCategoryExisting = factory(ProcessCategory::class)->create();
        $processCategory = factory(ProcessCategory::class)->create();
        $catUid = $processCategory->uid;
        $data = [
            "name" => $faker->name(),
            "status" => ProcessCategory::STATUS_ACTIVE,
        ];
        $response = $this->api('PUT', self::API_TEST_CATEGORY . $catUid, $data);
        $response->assertStatus(200);
        $response->assertJsonStructure();
        $processCategoryJson = $response->json();
        $processCategory = ProcessCategory::where('uid', $processCategoryJson['uid'])
            ->first();
        $this->assertNotNull($processCategory);
        $this->assertEquals($processCategory->uid, $processCategoryJson['uid']);
        $this->assertEquals($processCategory->name, $data['name']);

        //Validate required name
        $response = $this->api('PUT', self::API_TEST_CATEGORY . $catUid, ["name" => '']);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.required', ['attribute' => 'name']),
            $response->json()['errors']['name'][0]
        );

        //Validate 404 if category does not exists
        $response = $this->api('PUT', self::API_TEST_CATEGORY . 'DOES_NOT_EXISTS', $data);
        $response->assertStatus(404);

        //Validate that category name is unique
        $data = [
            "name" => $processCategoryExisting->name,
        ];
        $response = $this->api('PUT', self::API_TEST_CATEGORY . $catUid, $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.unique', ['attribute' => 'name']),
            $response->json()['errors']['name'][0]
        );

        //Validate invalid large name
        $data = [
            "name" => $faker->sentence(100),
        ];
        $response = $this->api('PUT', self::API_TEST_CATEGORY . $catUid, $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.max.string', ['attribute' => 'name', 'max' => 100]),
            $response->json()['errors']['name'][0]
        );
    }

    /**
     * Test the deletion of process categories.
     */
    public function testDeleteProcessCategory()
    {
        $processCategory = factory(ProcessCategory::class)->create();
        $catUid = $processCategory->uid;

        $response = $this->api('DELETE', self::API_TEST_CATEGORY . $catUid);
        $response->assertStatus(204);

        //Validate 404 if category does not exists
        $response = $this->api('DELETE', self::API_TEST_CATEGORY . 'DOES_NOT_EXISTS');
        $response->assertStatus(404);

        //Validate to do not delete category with processes
        $processCategory = factory(ProcessCategory::class)->create();
        $catUid = $processCategory->uid;
        factory(Process::class)->create([
            'process_category_id' => $processCategory->id
        ]);

    }

    /**
     * Test the show of process categories.
     */
    public function testShowProcessCategory()
    {
        $processCategory = factory(ProcessCategory::class)->create();
        $catUid = $processCategory->uid;

        $response = $this->api('GET', self::API_TEST_CATEGORY . $catUid);
        $response->assertStatus(200);
        $response->assertJsonStructure();
        $processCategoryJson = $response->json();
        $this->assertEquals($processCategory->uid, $processCategoryJson['uid']);
        $this->assertEquals($processCategory->name, $processCategoryJson['name']);

        //Validate 404 if category does not exists
        $response = $this->api('GET', self::API_TEST_CATEGORY . 'DOES_NOT_EXISTS');
        $response->assertStatus(404);
    }
}
