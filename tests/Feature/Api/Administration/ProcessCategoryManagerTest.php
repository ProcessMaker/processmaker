<?php

namespace Tests\Feature\Api\Administration;

use Faker\Factory as Faker;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessCategory;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class ProcessCategoryManagerTest extends ApiTestCase
{
    const API_TEST_CATEGORY = '/api/1.0/category/';
    const API_TEST_CATEGORIES = '/api/1.0/categories';

    /**
     * Test access control for the process category endpoints.
     */
    public function testAccessControl()
    {
        $user = factory(User::class)->create([
            'USR_PASSWORD' => Hash::make('password'),
            'USR_ROLE'     => Role::PROCESSMAKER_OPERATOR,
        ]);
        $this->auth($user->USR_USERNAME, 'password');
        $processCategory = factory(ProcessCategory::class)->create();
        $catUid = $processCategory->CATEGORY_UID;

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
        $admin = factory(User::class)->create([
            'USR_PASSWORD' => Hash::make('password'),
            'USR_ROLE'     => Role::PROCESSMAKER_ADMIN
        ]);
        $this->auth($admin->USR_USERNAME, 'password');
        //Create test categories
        $processCategory = factory(ProcessCategory::class)->create();
        factory(ProcessCategory::class)->create();
        factory(Process::class)->create([
            'PRO_CATEGORY' => $processCategory->CATEGORY_UID
        ]);

        $response = $this->api('GET', self::API_TEST_CATEGORIES);
        $response->assertStatus(200);
        $response->assertJsonStructure();
        $response->assertJsonFragment([
            [
                "cat_uid"             => $processCategory->CATEGORY_UID,
                "cat_name"            => $processCategory->CATEGORY_NAME,
                "cat_total_processes" => 1,
            ]
        ]);

        //Test filter
        $response = $this->api('GET', self::API_TEST_CATEGORIES . '?filter=' . urlencode($processCategory->CATEGORY_NAME));
        $response->assertStatus(200);
        $response->assertJsonStructure();
        $response->assertJsonFragment([
            [
                "cat_uid"             => $processCategory->CATEGORY_UID,
                "cat_name"            => $processCategory->CATEGORY_NAME,
                "cat_total_processes" => 1,
            ]
        ]);

        //Test filter not found
        $response = $this->api('GET', self::API_TEST_CATEGORIES . '?filter=NOT_FOUND_TEXT');
        $response->assertStatus(200);
        $response->assertJsonStructure();
        $this->assertCount(0, $response->json());

        //Test invalid start
        $response = $this->api('GET', self::API_TEST_CATEGORIES . '?start=INVALID');
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.numeric', ['attribute' => 'start']), $response->json()['error']['message']
        );

        //Test invalid limit
        $response = $this->api('GET', self::API_TEST_CATEGORIES . '?limit=INVALID');
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.numeric', ['attribute' => 'limit']), $response->json()['error']['message']
        );

        //Test start and limit
        $response = $this->api('GET', self::API_TEST_CATEGORIES . '?filter=' . urlencode($processCategory->CATEGORY_NAME));
        $response->assertStatus(200);
        $response->assertJsonStructure();
        $response->assertJsonFragment([
            [
                "cat_uid"             => $processCategory->CATEGORY_UID,
                "cat_name"            => $processCategory->CATEGORY_NAME,
                "cat_total_processes" => 1,
            ]
        ]);

        //Test start and limit
        $response = $this->api('GET', self::API_TEST_CATEGORIES . '?filter=' . urlencode($processCategory->CATEGORY_NAME) . '&start=0&limit=1');
        $response->assertStatus(200);
        $response->assertJsonStructure();
        $response->assertJsonFragment([
            [
                "cat_uid"             => $processCategory->CATEGORY_UID,
                "cat_name"            => $processCategory->CATEGORY_NAME,
                "cat_total_processes" => 1,
            ]
        ]);
        $this->assertCount(1, $response->json());
    }

    /**
     * Test the creation of process categories.
     */
    public function testCreateProcessCategory()
    {
        $admin = factory(User::class)->create([
            'USR_PASSWORD' => Hash::make('password'),
            'USR_ROLE'     => Role::PROCESSMAKER_ADMIN
        ]);
        $this->auth($admin->USR_USERNAME, 'password');
        $faker = Faker::create();

        $processCategory = factory(ProcessCategory::class)->make();
        $data = [
            "cat_name" => $processCategory->CATEGORY_NAME,
        ];
        $response = $this->api('POST', self::API_TEST_CATEGORY, $data);
        $response->assertStatus(201);
        $response->assertJsonStructure();
        $processCategoryJson = $response->json();
        $processCategory = ProcessCategory::where('CATEGORY_UID', $processCategoryJson['cat_uid'])
            ->first();
        $this->assertNotNull($processCategory);
        $response->assertJsonFragment([
            [
                "cat_uid"             => $processCategory->CATEGORY_UID,
                "cat_name"            => $processCategory->CATEGORY_NAME,
                "cat_total_processes" => 0,
            ]
        ]);

        //Validate required cat_name
        $response = $this->api('POST', self::API_TEST_CATEGORY, []);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.required', ['attribute' => 'cat name']), $response->json()['error']['message']
        );

        //Validate creation of duplicated category
        $response = $this->api('POST', self::API_TEST_CATEGORY, $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.custom.cat_name.unique', ['attribute' => 'cat_name']), $response->json()['error']['message']
        );

        //Validate invalid large name
        $data = [
            "cat_name" => $faker->sentence(100),
        ];
        $response = $this->api('POST', self::API_TEST_CATEGORY, $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.max.string', ['attribute' => 'cat name', 'max' => 100]), $response->json()['error']['message']
        );
    }

    /**
     * Test the update of process categories.
     */
    public function testUpdateProcessCategory()
    {
        $admin = factory(User::class)->create([
            'USR_PASSWORD' => Hash::make('password'),
            'USR_ROLE'     => Role::PROCESSMAKER_ADMIN
        ]);
        $this->auth($admin->USR_USERNAME, 'password');
        $faker = Faker::create();

        $processCategoryExisting = factory(ProcessCategory::class)->create();
        $processCategory = factory(ProcessCategory::class)->create();
        $catUid = $processCategory->CATEGORY_UID;
        $data = [
            "cat_name" => $faker->name(),
        ];
        $response = $this->api('PUT', self::API_TEST_CATEGORY . $catUid, $data);
        $response->assertStatus(200);
        $response->assertJsonStructure();
        $processCategoryJson = $response->json();
        $processCategory = ProcessCategory::where('CATEGORY_UID', $processCategoryJson['cat_uid'])
            ->first();
        $this->assertNotNull($processCategory);
        $this->assertEquals($processCategory->CATEGORY_UID, $processCategoryJson['cat_uid']);
        $this->assertEquals($processCategory->CATEGORY_NAME, $data['cat_name']);

        //Validate required cat_name
        $response = $this->api('PUT', self::API_TEST_CATEGORY . $catUid, []);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.required', ['attribute' => 'cat name']), $response->json()['error']['message']
        );

        //Validate 404 if category does not exists
        $response = $this->api('PUT', self::API_TEST_CATEGORY . 'DOES_NOT_EXISTS', $data);
        $response->assertStatus(404);

        //Validate that category name is unique
        $data = [
            "cat_name" => $processCategoryExisting->CATEGORY_NAME,
        ];
        $response = $this->api('PUT', self::API_TEST_CATEGORY . $catUid, $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.custom.cat_name.unique', ['attribute' => 'cat_name']), $response->json()['error']['message']
        );

        //Validate invalid large name
        $data = [
            "cat_name" => $faker->sentence(100),
        ];
        $response = $this->api('PUT', self::API_TEST_CATEGORY . $catUid, $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.max.string', ['attribute' => 'cat name', 'max' => 100]), $response->json()['error']['message']
        );
    }

    /**
     * Test the deletion of process categories.
     */
    public function testDeleteProcessCategory()
    {
        $admin = factory(User::class)->create([
            'USR_PASSWORD' => Hash::make('password'),
            'USR_ROLE'     => Role::PROCESSMAKER_ADMIN
        ]);
        $this->auth($admin->USR_USERNAME, 'password');

        $processCategory = factory(ProcessCategory::class)->create();
        $catUid = $processCategory->CATEGORY_UID;

        $response = $this->api('DELETE', self::API_TEST_CATEGORY . $catUid);
        $response->assertStatus(204);

        //Validate 404 if category does not exists
        $response = $this->api('DELETE', self::API_TEST_CATEGORY . 'DOES_NOT_EXISTS');
        $response->assertStatus(404);

        //Validate to do not delete category with processes
        $processCategory = factory(ProcessCategory::class)->create();
        $catUid = $processCategory->CATEGORY_UID;
        factory(Process::class)->create([
            'PRO_CATEGORY' => $processCategory->CATEGORY_UID
        ]);
        $response = $this->api('DELETE', self::API_TEST_CATEGORY . $catUid);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.custom.processCategory.process_category_manager.category_does_not_have_processes',
               ['attribute' => 'cat_name']), $response->json()['error']['message']
        );
    }

    /**
     * Test the show of process categories.
     */
    public function testShowProcessCategory()
    {
        $admin = factory(User::class)->create([
            'USR_PASSWORD' => Hash::make('password'),
            'USR_ROLE'     => Role::PROCESSMAKER_ADMIN
        ]);
        $this->auth($admin->USR_USERNAME, 'password');

        $processCategory = factory(ProcessCategory::class)->create();
        $catUid = $processCategory->CATEGORY_UID;

        $response = $this->api('GET', self::API_TEST_CATEGORY . $catUid);
        $response->assertStatus(200);
        $response->assertJsonStructure();
        $processCategoryJson = $response->json();
        $this->assertEquals($processCategory->CATEGORY_UID, $processCategoryJson['cat_uid']);
        $this->assertEquals($processCategory->CATEGORY_NAME, $processCategoryJson['cat_name']);

        //Validate 404 if category does not exists
        $response = $this->api('GET', self::API_TEST_CATEGORY . 'DOES_NOT_EXISTS');
        $response->assertStatus(404);
    }
}
