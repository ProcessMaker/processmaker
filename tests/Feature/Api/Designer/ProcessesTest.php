<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessCategory;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\TestCase;
use ProcessMaker\Transformers\ProcessTransformer;

/**
 * Tests routes related to processes / CRUD related methods
 *
 */
class ProcessesTest extends TestCase
{
    use DatabaseTransactions;

    const API_TEST_PROCESS = '/api/1.0/processes';

    const STRUCTURE = [
        'uid',
        'name',
        'description',
        'parent_process_id',
        'time',
        'timeunits',
        'status',
        'type',
        'show_map',
        'show_message',
        'create_script_id',
        'open_script_id',
        'deleted_script_id',
        'canceled_script_id',
        'paused_script_id',
        'reassigned_script_id',
        'unpaused_script_id',
        'visibility',
        'show_delegate',
        'show_dynaform',
        'created_at',
        'updated_at',
        'user',
        'height',
        'width',
        'title_x',
        'title_y',
        'debug',
        'dynaforms',
        'derivation_screen_template',
        'cost',
        'unit_cost',
        'itee',
        'action_done',
        'executable',
        'closed',
        'target_namespace',
        'expression_language',
        'type_language',
        'exporter',
        'exporter_version',
        'author',
        'author_version',
        'original_source',
        'category'
    ];

    /**
     * Tests to determine that reaching the processes endpoint is protected by an authenticated user
     */
    public function testUnauthenticated()
    {
        // Not creating a user, not logging in
        // Now attempt to connect to api
        $response = $this->json('GET', self::API_TEST_PROCESS);
        $response->assertStatus(401);
    }

    /**
     * Test to ensure our endpoints are protected by permissions (PM_FACTORY permission is needed)
     */
    public function testUnauthorized()
    {
        // Create our user we will log in with, but not have the needed permissions
        $user = factory(User::class)->create([
            'role_id' => null,
            'password' => Hash::make('password'),
        ]);
        
        // Now try our api endpoint, but this time, will get a 403 Unauthorized
        $response = $this->actingAs($user, 'api')->json('GET', self::API_TEST_PROCESS);
        $response->assertStatus(403);
    }

    /**
     * Create new process with template
     */
    public function testCreateProcessTemplate()
    {
        $user = $this->authenticateAsAdmin();
        $faker = Faker::create();

        $url = self::API_TEST_PROCESS . '/create';
        $response = $this->actingAs($user, 'api')->json('POST', $url, [
            'name' => $faker->sentence(3),
            'description' => $faker->sentence(3),
            'category_uid' => factory(ProcessCategory::class)->create()->uid

        ]);
        //validating the answer is correct.
        $response->assertStatus(201);
        //Check structure of response.
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Test to verify our processes listing api endpoint works without any filters
     */
    public function testProcessesListing(): void
    {
        $user = $this->authenticateAsAdmin();
        // Create some processes
        factory(Process::class, 5)->create();
        $response = $this->actingAs($user, 'api')->json('GET', self::API_TEST_PROCESS);
        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);
        // Verify we have a total of 7 results (our 5 plus processes plus our created processes)
        $this->assertCount(7, $data['data']);
        $this->assertEquals(7, $data['meta']['total']);
    }

    /**
     * Tests filtering processes by a filter which will not match
     */
    public function testProcessesListingWithFilterNoMatches()
    {
        $user = $this->authenticateAsAdmin();
        // Create some processes
        // Chances are the title/description will not include our invalid filter
        factory(Process::class, 5)->create();
        $response = $this->actingAs($user, 'api')->json('GET', self::API_TEST_PROCESS . '?filter=invalidfilter');
        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);
        // Make sure we get no results.
        $this->assertCount(0, $data['data']);
        $this->assertEquals(0, $data['meta']['total']);
    }

    /**
     * Tests filtering processes by a filter which matches one process on name field
     */
    public function testProcessesListingWithFilterWithMatchesOnName()
    {
        $user = $this->authenticateAsAdmin();
        // Create some processes, keep our list
        factory(Process::class, 20)->create();
        // Now create a process with some data which will match
        factory(Process::class)->create([
            'name' => 'This is a test process',
            'description' => 'A test description'
        ]);
        // Test filtering, matching middle of name
        $response = $this->actingAs($user, 'api')->json('GET', self::API_TEST_PROCESS . '?filter=' . urlencode('is a test'));
        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);
        // Make sure we get 1 result.
        $this->assertCount(1, $data['data']);
        $this->assertEquals(1, $data['meta']['total']);
        // Ensure our name is the same
        $this->assertEquals('This is a test process', $data['data'][0]['name']);
        // Ensure description is the same
        $this->assertEquals('A test description', $data['data'][0]['description']);

    }

    /**
     * Tests filtering processes by a filter which matches one process on description field
     */
    public function testProcessesListingWithFilterWithMatchesOnDescription()
    {
        $user = $this->authenticateAsAdmin();
        // Create some processes, keep our list
        factory(Process::class, 5)->create();
        // Now create a process with a description
        factory(Process::class)->create([
            'description' => 'Another test process'
        ]);
        // Test filtering, matching middle of description
        $response = $this->actingAs($user, 'api')->json('GET', self::API_TEST_PROCESS . '?filter=' . urlencode('other test'));
        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);
        // Make sure we get 1 result.
        $this->assertCount(1, $data['data']);
        $this->assertEquals(1, $data['meta']['total']);
    }

    /**
     * Tests filtering processes by a filter which matches one process on category name
     */
    public function testProcessesListingWithFilterWithMatchesOnCategoryName()
    {
        $user = $this->authenticateAsAdmin();
        // Create some processes, keep our list
        factory(Process::class, 5)->create();
        // Now test with a matched category
        $category = factory(ProcessCategory::class)->create([
            'name' => 'My Test Category'
        ]);
        // Create process with that category defined
        $process = factory(Process::class)->create([
            'process_category_id' => $category->id
        ]);
        $response = $this->actingAs($user, 'api')->json('GET', self::API_TEST_PROCESS . '?filter=' . urlencode('Test Cat'));
        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);
        // Make sure we get 1 result.
        $this->assertCount(1, $data['data']);
        $this->assertEquals(1, $data['meta']['total']);
    }

    /**
     * Test to fetch a single item with a uid that does not match a process
     */
    public function testProcessesSingleItemNotFound()
    {
        $user = $this->authenticateAsAdmin();
        $response = $this->actingAs($user, 'api')->json('GET', self::API_TEST_PROCESS . '/invalid-uid');
        $response->assertStatus(404);
    }

    /**
     * Test successfully retrieving a single process item, matching the transformed data expected
     */
    public function testProcessesSingleItemFound()
    {
        $user = $this->authenticateAsAdmin();
        $process = factory(Process::class)->create();
        // Fetch from DB to ensure we're getting all columns
        $process = Process::with(['category', 'user'])->find($process->id);
        $response = $this->actingAs($user, 'api')->json('GET', self::API_TEST_PROCESS . '/' . $process->uid);
        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);
        $expected = fractal($process, new ProcessTransformer())->toArray();
        $this->assertEquals($expected, $data);
    }


    public function testProcessesSingleItemFoundWithCategory()
    {
        $category = factory(ProcessCategory::class)->create();
        $user = $this->authenticateAsAdmin();
        $process = factory(Process::class)->create([
            'process_category_id' => $category->id
        ]);
        // Fetch from DB to ensure we're getting all columns
        $process = Process::with(['category', 'user'])->find($process->id);
        $response = $this->actingAs($user, 'api')->json('GET', self::API_TEST_PROCESS . '/' . $process->uid);
        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);
        $expected = fractal($process, new ProcessTransformer())->toArray();
        $this->assertEquals($expected, $data);
        // Ensure that the category NAME is property set to the name of the category we created
        $this->assertEquals($category->name, $data['category']);
    }

    /**
     * Test get a list of the files in a process.
     */
    public function testGetPublic()
    {
        $admin = $this->authenticateAsAdmin();
        //Create a test process using factories
        factory(Process::class)->create([
            'user_id' => $admin->id
        ]);
        $response = $this->actingAs($admin, 'api')->json('GET', self::API_TEST_PROCESS);
        $response->assertStatus(200);
        // Verify we have a total of 3 results (our 2 plus processes plus our created processes)
        $this->assertEquals(3, $response->original->meta->total);
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * Test get a list of the files filter
     */
    public function testGetPublicFilter()
    {
        $user = $this->authenticateAsAdmin();
        //Create a test process using factories
        $process = factory(Process::class)->create([
            'user_id' => $user->id
        ]);
        $response = $this->actingAs($user, 'api')->json('GET', self::API_TEST_PROCESS. '?filter=' . urlencode($process->name));
        $response->assertStatus(200);

        $this->assertEquals(1, $response->original->meta->total);
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * Test get a list of the files filter
     */
    public function testGetPublicFilterWithParameters()
    {
        $user = $this->authenticateAsAdmin();
        //Create a test process using factories
        $name = 'Name process Test';
        factory(Process::class)->create([
            'name' => $name,
            'user_id' => $user->id
        ]);
        $perPage = Faker::create()->randomDigitNotNull;
        $query = '?page=1&per_page=' . $perPage . '&order_by=delegate_date&order_direction=DESC&filter=' . urlencode($name);

        $response = $this->actingAs($user, 'api')->json('GET', self::API_TEST_PROCESS. '?filter=' . $query);
        $response->assertStatus(200);

        //verify response in meta
        $this->assertEquals(1, $response->original->meta->total);
        $this->assertEquals(1, $response->original->meta->count);
        $this->assertEquals($perPage, $response->original->meta->per_page);
        $this->assertEquals(1, $response->original->meta->current_page);
        $this->assertEquals(1, $response->original->meta->total_pages);
        $this->assertEquals($name, $response->original->meta->filter);
    }

    /**
     * Get the process definition.
     *
     */
    public function testGetDefinition()
    {
        //Login as admin user
        $admin = $this->authenticateAsAdmin();

        $process = factory(Process::class)->create([
            'user_id' => $admin->id
        ]);

        //Get the json from the end point
        $response = $this->actingAs($admin, 'api')->json('GET', self::API_TEST_PROCESS . '/' . $process->uid);
        $response->assertStatus(200);
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Test delete a process.
     *
     */
    public function testDelete()
    {
        $admin = $this->authenticateAsAdmin();
        // We need a process
        $process = factory(Process::class)->create([
            'user_id' => $admin->id
        ]);

        //Delete process
        $response = $this->actingAs($admin, 'api')->json('DELETE', self::API_TEST_PROCESS . '/' . $process->uid);
        $response->assertStatus(204);
        $this->assertDatabaseMissing($process->getTable(), [
            'id' => $process->id
        ]);

    }

    /**
     * Test delete a process not exists.
     *
     */
    public function testDeleteProcessNotExists()
    {
        $user = $this->authenticateAsAdmin();

        //Delete process
        $response = $this->actingAs($user, 'api')->json('DELETE', self::API_TEST_PROCESS . '/' . factory(Process::class)->make()->uid);
        $response->assertStatus(404);
    }

    /**
     * Create an login API as an administrator user.
     *
     * @return User
     */
    private function authenticateAsAdmin(): User
    {
        $admin = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);
        return $admin;
    }
}
