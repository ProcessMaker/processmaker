<?php

namespace Tests\Feature\Api\Designer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessCategory;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;
use ProcessMaker\Transformers\ProcessTransformer;


/**
 * Tests routes related to processes / CRUD related methods
 *
 */
class ProcessesTest extends ApiTestCase
{
    use DatabaseTransactions;

    const API_TEST_PROCESS = '/api/1.0/processes';

    /**
     * Tests to determine that reaching the processes endpoint is protected by an authenticated user
     */
    public function testUnauthenticated()
    {
        // Not creating a user, not logging in
        // Now attempt to connect to api
        $response = $this->api('GET', self::API_TEST_PROCESS);
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
        $this->auth($user->username, 'password');
        // Now try our api endpoint, but this time, will get a 403 Unauthorized
        $response = $this->api('GET', self::API_TEST_PROCESS);
        $response->assertStatus(403);
    }

    /**
     * Test to verify our processes listing api endpoint works without any filters
     */
    public function testProcessesListing(): void
    {
        $user = $this->authenticateAsAdmin();
        // Create some processes
        factory(Process::class, 5)->create();
        $response = $this->api('GET', self::API_TEST_PROCESS);
        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);
        $this->assertCount(5, $data['data']);
        $this->assertEquals(5, $data['meta']['total']);
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
        $response = $this->api('GET', self::API_TEST_PROCESS . '?filter=invalidfilter');
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
        $response = $this->api('GET', self::API_TEST_PROCESS . '?filter=' . urlencode('is a test'));
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
        $response = $this->api('GET', self::API_TEST_PROCESS . '?filter=' . urlencode('other test'));
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
        $response = $this->api('GET', self::API_TEST_PROCESS . '?filter=' . urlencode('Test Cat'));
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
        $response = $this->api('GET', self::API_TEST_PROCESS . '/invalid-uid');
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
        $process = Process::find($process->id);
        $response = $this->api('GET', self::API_TEST_PROCESS . '/' . $process->uid);
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
        $process = Process::find($process->id);
        $response = $this->api('GET', self::API_TEST_PROCESS . '/' . $process->uid);
        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);
        $expected = fractal($process, new ProcessTransformer())->toArray();
        $this->assertEquals($expected, $data);
        // Ensure that the category NAME is property set to the name of the category we created
        $this->assertEquals($category->name, $data['category']);
    }



    /**
     * Test get a list of the files in a project.
     * 
     * @todo, we are not going to return the json/BPMN design in our get.  Instead we should have it in a route
     * of processes/{process_id}/bpmn (GET/POST/DELETE)
     * So please refactor as such
     *
     */
    public function testGetPublic()
    {
        $this->markTestSkipped('Process Manager tests need to be refactored');
        //Login as an PROCESSMAKER_ADMIN user.
        $admin = $this->authenticateAsAdmin();

        //Create a test process using factories
        $process = factory(Process::class)->create([
            'user_id' => $admin->id
        ]);
        $response = $this->api('GET', self::API_TEST_PROJECT);
        $response->assertStatus(200);
        $response->assertJsonFragment([
            [
                "prj_uid"         => $process->uid,
                "prj_name"        => $process->name,
                "prj_description" => $process->description,
                "prj_category"    => null,
                "prj_type"        => $process->type,
                "prj_create_date" => $process->created_at->toIso8601String(),
                "prj_update_date" => $process->updated_at->toIso8601String(),
                "prj_status"      => $process->status,
            ]
        ]);

        //Test filter by process name
        $response = $this->api('GET', self::API_TEST_PROJECT . '?filter=' . urlencode($process->PRO_NAME));
        $response->assertStatus(200);
        $response->assertJsonFragment([
            [
                "prj_uid"         => $process->uid,
                "prj_name"        => $process->name,
                "prj_description" => $process->description,
                "prj_category"    => null,
                "prj_type"        => $process->type,
                "prj_create_date" => $process->created_at->toIso8601String(),
                "prj_update_date" => $process->updated_at->toIso8601String(),
                "prj_status"      => $process->status,
            ]
        ]);

        //Test filter to return empty result
        $response = $this->api('GET', self::API_TEST_PROJECT . '?filter=NOT_FOUND_TEXT');
        $response->assertStatus(200);
        $this->assertCount(0, $response->json());

        //Test invalid start
        $response = $this->api('GET', self::API_TEST_PROJECT . '?start=INVALID');
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.numeric', ['attribute' => 'start']), $response->json()['error']['message']
        );

        //Test invalid limit
        $response = $this->api('GET', self::API_TEST_PROJECT . '?limit=INVALID');
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.numeric', ['attribute' => 'limit']), $response->json()['error']['message']
        );

        //Test filter start and limit
        $response = $this->api('GET',
                               self::API_TEST_PROJECT . '?filter=' . urlencode($process->name) . '&start=0&limit=1');
        $response->assertStatus(200);
        $response->assertJsonStructure();
        $response->assertJsonFragment([
            [
                "prj_uid"         => $process->uid,
                "prj_name"        => $process->name,
                "prj_description" => $process->description,
                "prj_category"    => $process->process_category_id,
                "prj_type"        => $process->type,
                "prj_create_date" => $process->created_at->toIso8601String(),
                "prj_update_date" => $process->updated_at->toIso8601String(),
                "prj_status"      => $process->status,
            ]
        ]);
    }

    /**
     * Get the process definition in a json format.
     *
     */
    public function testGetDefinition()
    {
        $this->markTestSkipped('Process Manager tests need to be refactored');
        //Login as admin user
        $admin = $this->authenticateAsAdmin();

        //Create a test process using factories
        /**
         *                   +--[A1]--(E1)
         * (E0)--[A0]--<G0>--+
         *         |         +--[A2]--+
         *         |                  |
         *         +------------------+
         */
        $process = factory(Process::class)->create([
            'user_id' => $admin->id
        ]);
        $diagram = factory(Diagram::class)->create([
            'process_id' => $process->id,
        ]);
        $activities = factory(Activity::class, 3)->create([
                'process_id'  => $process->id
            ])->each(function (Activity $activity) use ($diagram) {
            $activity->createShape($diagram);
        });
        $events = factory(Event::class, 2)->create([
                'process_id' => $process->id
            ])->each(function (Event $event) use ($diagram) {
            $event->createShape($diagram);
        });
        $gateway = factory(Gateway::class)->create([
            'process_id' => $process->id
        ]);
        $gateway->createShape($diagram);
        factory(Artifact::class, 3)->create([
                'process_id' => $process->id
            ])->each(function (Artifact $artifact) use ($diagram) {
            $artifact->createShape($diagram);
        });
        factory(Laneset::class, 3)->create([
                'process_id' => $process->id
            ])->each(function (Laneset $laneset) use ($diagram) {
            $laneset->createShape($diagram);
        });
        factory(Lane::class, 3)->create([
                'process_id' => $process->id,
            ])->each(function (Lane $lane) use ($diagram) {
            $lane->createShape($diagram);
        });

        $events[0]->createFlowTo($activities[0], [
                'FLO_STATE' => [
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                ]
            ]);
        $activities[0]->createFlowTo($gateway, [
                'FLO_STATE' => [
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                ]
            ]);
        $gateway->createFlowTo($activities[1], [
                'FLO_STATE' => [
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                ]
            ])
            ->createFlowTo($activities[2], [
                'FLO_STATE' => [
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                ]
            ]);
        $activities[1]->createFlowTo($events[1], [
                'FLO_STATE' => [
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                ]
            ]);
        $activities[2]->createFlowTo($activities[0], [
                'FLO_STATE' => [
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                ]
            ]);

        //Get the json from the end point
        $response = $this->api('GET', self::API_TEST_PROJECT . '/' . $process->uid);
        $response->assertStatus(200);
        $expected = $process->toArray();
        $result = $response->json();

        //Verify the process structure
        $response->assertJsonStructure([
            'prj_uid',
            'prj_name',
            'prj_description',
            'prj_target_namespace',
            'prj_expresion_language',
            'prj_type_language',
            'prj_exporter',
            'prj_exporter_version',
            'prj_create_date',
            'prj_update_date',
            'prj_author',
            'prj_author_version',
            'prj_original_source',
            'diagram'
        ]);

        //Verify the diagram structure
        $response->assertJsonStructure([
            'dia_uid',
            'prj_uid',
            'dia_name',
            'dia_is_closable',
            'pro_uid',
            'activities',
            'events',
            'gateways',
            'flows',
            'artifacts',
            'laneset',
            'lanes',
        ], $result['diagram']);

        //Verify the activities structure
        $response->assertJsonStructure(['*' => [
            'act_cancel_remaining_instances',
            'act_completion_condition',
            'act_completion_quantity',
            'act_default_flow',
            'act_implementation',
            'act_instantiate',
            'act_is_adhoc',
            'act_is_collapsed',
            'act_is_for_compensation',
            'act_is_global',
            'act_loop_behavior',
            'act_loop_cardinality',
            'act_loop_condition',
            'act_loop_maximum',
            'act_loop_type',
            'act_master_diagram',
            'act_method',
            'act_name',
            'act_ordering',
            'act_protocol',
            'act_referer',
            'act_script',
            'act_script_type',
            'act_start_quantity',
            'act_task_type',
            'act_test_before',
            'act_type',
            'act_uid',
            'bou_container',
            'bou_element',
            'bou_height',
            'bou_width',
            'bou_x',
            'bou_y',
        ]], $result['diagram']['activities']);

        //Verify the events structure
        $response->assertJsonStructure(['*' => [
            'bou_container',
            'bou_element',
            'bou_height',
            'bou_width',
            'bou_x',
            'bou_y',
            'evn_activity_ref',
            'evn_behavior',
            'evn_cancel_activity',
            'evn_error_code',
            'evn_error_name',
            'evn_escalation_code',
            'evn_escalation_name',
            'evn_is_interrupting',
            'evn_marker',
            'evn_message',
            'evn_name',
            'evn_operation_implementation_ref',
            'evn_operation_name',
            'evn_time_cycle',
            'evn_time_date',
            'evn_time_duration',
            'evn_type',
            'evn_uid',
            'evn_wait_for_completion',
        ]], $result['diagram']['events']);

        //Verify the gateways structure
        $response->assertJsonStructure(['*' => [
            'bou_container',
            'bou_element',
            'bou_height',
            'bou_width',
            'bou_x',
            'bou_y',
            'gat_activation_count',
            'gat_default_flow',
            'gat_direction',
            'gat_event_gateway_type',
            'gat_instantiate',
            'gat_name',
            'gat_type',
            'gat_uid',
            'gat_waiting_for_start',
        ]], $result['diagram']['gateways']);

        //Verify the flows structure
        $response->assertJsonStructure(['*' => [
            'flo_condition',
            'flo_element_dest',
            'flo_element_dest_type',
            'flo_element_origin',
            'flo_element_origin_type',
            'flo_is_inmediate',
            'flo_name',
            'flo_position',
            'flo_state',
            'flo_type',
            'flo_uid',
            'flo_x1',
            'flo_x2',
            'flo_y1',
            'flo_y2',
        ]], $result['diagram']['flows']);
        $response->assertJsonStructure(['*' => [
            'x',
            'y',
        ]], $result['diagram']['flows'][0]['flo_state']);

        //Verify the artifacts structure
        $response->assertJsonStructure(['*' => [
            'art_category_ref',
            'art_name',
            'art_type',
            'art_uid',
            'bou_container',
            'bou_element',
            'bou_height',
            'bou_width',
            'bou_x',
            'bou_y',
        ]], $result['diagram']['artifacts']);

        //Verify the lanesets structure
        $response->assertJsonStructure(['*' => [
            'bou_container',
            'bou_element',
            'bou_rel_position',
            'bou_x',
            'bou_y',
            'bou_width',
            'bou_height',
            'dia_uid',
            'element_uid',
            'lns_is_horizontal',
            'lns_name',
            'lns_parent_lane',
            'lns_state',
            'lns_uid',
            'pro_uid',
        ]], $result['diagram']['laneset']);

        //Verify the lanes structure
        $response->assertJsonStructure(['*' => [
            'bou_container',
            'bou_element',
            'bou_height',
            'bou_rel_position',
            'bou_width',
            'bou_x',
            'bou_y',
            'dia_uid',
            'element_uid',
            'lan_child_laneset',
            'lan_is_horizontal',
            'lan_name',
            'lan_uid',
            'lns_uid',
        ]], $result['diagram']['lanes']);
    }

    /**
     * Test delete a process.
     *
     */
    public function testDelete()
    {
        $this->markTestSkipped('Process Manager tests need to be refactored');
        $admin = $this->authenticateAsAdmin();
        // We need a process
        $process = factory(Process::class)->create([
            'user_id' => $admin->id
        ]);
        $diagram = factory(Diagram::class)->create([
            'process_id' => $process->id,
        ]);
        $activities = factory(Activity::class, 3)->create([
                'process_id'  => $process->id
            ])->each(function (Activity $activity) use ($diagram) {
            $activity->createShape($diagram);
        });
        $events = factory(Event::class, 2)->create([
                'process_id' => $process->id
            ])->each(function (Event $event) use ($diagram) {
            $event->createShape($diagram);
        });
        $gateway = factory(Gateway::class)->create([
            'process_id' => $process->id
        ]);
        $gateway->createShape($diagram);
        $artifacts = factory(Artifact::class, 3)->create([
                'process_id' => $process->id
            ])->each(function (Artifact $artifact) use ($diagram) {
            $artifact->createShape($diagram);
        });
        $lanesets = factory(Laneset::class, 3)->create([
                'process_id' => $process->id
            ])->each(function (Laneset $laneset) use ($diagram) {
            $laneset->createShape($diagram);
        });
        $lanes = factory(Lane::class, 3)->create([
                'process_id' => $process->id,
            ])->each(function (Lane $lane) use ($diagram) {
            $lane->createShape($diagram);
        });

        $events[0]->createFlowTo($activities[0], [
                'FLO_STATE' => [
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                ]
            ]);
        $activities[0]->createFlowTo($gateway, [
                'FLO_STATE' => [
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                ]
            ]);
        $gateway->createFlowTo($activities[1], [
                'FLO_STATE' => [
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                ]
            ])
            ->createFlowTo($activities[2], [
                'FLO_STATE' => [
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                ]
            ]);
        $activities[1]->createFlowTo($events[1], [
                'FLO_STATE' => [
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                ]
            ]);
        $activities[2]->createFlowTo($activities[0], [
                'FLO_STATE' => [
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                    ['x'=> random_int(0, 1040), 'y'=> random_int(0, 1040)],
                ]
            ]);

        //Delete process
        $response = $this->api('DELETE', self::API_TEST_PROJECT . '/' . $process->uid);
        $response->assertStatus(204);
        $this->assertDatabaseMissing($process->getTable(), [
            'id' => $process->id
        ]);

        //Verify that the components where deleted (activities, gateway, events, flows,...)
        $this->assertDatabaseMissing($diagram->getTable(), [
            'DIA_UID' => $diagram->DIA_UID
        ]);
        $this->assertDatabaseMissing($activities[0]->getTable(), [
            'ACT_UID' => $activities[0]->ACT_UID
        ]);
        $this->assertDatabaseMissing($events[0]->getTable(), [
            'EVN_UID' => $events[0]->EVN_UID
        ]);
        $this->assertDatabaseMissing($gateway->getTable(), [
            'GAT_UID' => $gateway->GAT_UID
        ]);
        $this->assertDatabaseMissing($artifacts[0]->getTable(), [
            'ART_UID' => $artifacts[0]->ART_UID
        ]);
        $this->assertDatabaseMissing($lanesets[0]->getTable(), [
            'LNS_UID' => $lanesets[0]->LNS_UID
        ]);
        $this->assertDatabaseMissing($lanes[0]->getTable(), [
            'LAN_UID' => $lanes[0]->LAN_UID
        ]);

        //@todo Verify non flow elements owned by process where deleted (reports, documents,...)

        //Validate 404 if process does not exists
        $response = $this->api('DELETE', self::API_TEST_PROJECT . 'DOES_NOT_EXISTS');
        $response->assertStatus(404);

        //Test to delete a process with cases
        $process = factory(Process::class)->create([
            'user_id' => $admin->id
        ]);
        $application = factory(Application::class)->create([
            'process_id'  => $process->id,
        ]);
        $response = $this->api('DELETE', self::API_TEST_PROJECT . '/' . $process->uid);
        $response->assertStatus(422);

        //Verify that process was not deleted
        $this->assertDatabaseHas($process->getTable(), [
            'id' => $process->id
        ]);
    }

    /**
     * Create an login API as an administrator user.
     *
     * @return User
     */
    private function authenticateAsAdmin()
    {
        $admin = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);
        $this->auth($admin->username, 'password');
        return $admin;
    }
}
