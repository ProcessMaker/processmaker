<?php

namespace Tests\Feature\Api\Designer;

use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Activity;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\Artifact;
use ProcessMaker\Model\Diagram;
use ProcessMaker\Model\Event;
use ProcessMaker\Model\Gateway;
use ProcessMaker\Model\Lane;
use ProcessMaker\Model\Laneset;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

/**
 * Process Manager tests
 *
 * @package \ProcessMaker\Managers
 */
class ProcessManagerTest extends ApiTestCase
{
    const API_TEST_PROJECT = '/api/1.0/project';

    /**
     * Validate access to process end points.
     *
     */
    public function testAccessControl()
    {
        //Login with an PROCESSMAKER_OPERATOR user.
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_OPERATOR)->first()->id
        ]);
        $this->auth($user->username, 'password');
        $process = factory(Process::class)->create([
            'PRO_CREATE_USER' => $user->USR_UID
        ]);

        //Create a test process using factories
        $process = factory(Process::class)->create([
            'PRO_CREATE_USER' => $user->USR_UID
        ]);
        $diagram = factory(Diagram::class)->create([
            'PRO_ID' => $process->PRO_ID,
        ]);

        //Validate does not have access to list of processes.
        $response = $this->api('GET', self::API_TEST_PROJECT);
        $response->assertStatus(403);

        //Validate does not have access to get a process definition.
        $response = $this->api('GET', self::API_TEST_PROJECT . '/' . $process->PRO_UID);
        $response->assertStatus(403);

        //Validate does not have access to delete a process.
        $response = $this->api('DELETE', self::API_TEST_PROJECT . '/' . $process->PRO_UID);
        $response->assertStatus(403);
    }

    /**
     * Test get a list of the files in a project.
     *
     */
    public function testGetPublic()
    {
        //Login as an PROCESSMAKER_ADMIN user.
        $admin = $this->authenticateAsAdmin();

        //Create a test process using factories
        $process = factory(Process::class)->create([
            'PRO_CREATE_USER' => $admin->USR_UID
        ]);
        $response = $this->api('GET', self::API_TEST_PROJECT);
        $response->assertStatus(200);
        $response->assertJsonFragment([
            [
                "prj_uid"         => $process->PRO_UID,
                "prj_name"        => $process->PRO_NAME,
                "prj_description" => $process->PRO_DESCRIPTION,
                "prj_category"    => $process->PRO_CATEGORY,
                "prj_type"        => $process->PRO_TYPE,
                "prj_create_date" => $process->PRO_CREATE_DATE->toIso8601String(),
                "prj_update_date" => $process->PRO_UPDATE_DATE->toIso8601String(),
                "prj_status"      => $process->PRO_STATUS,
            ]
        ]);

        //Test filter by process name
        $response = $this->api('GET', self::API_TEST_PROJECT . '?filter=' . urlencode($process->PRO_NAME));
        $response->assertStatus(200);
        $response->assertJsonFragment([
            [
                "prj_uid"         => $process->PRO_UID,
                "prj_name"        => $process->PRO_NAME,
                "prj_description" => $process->PRO_DESCRIPTION,
                "prj_category"    => $process->PRO_CATEGORY,
                "prj_type"        => $process->PRO_TYPE,
                "prj_create_date" => $process->PRO_CREATE_DATE->toIso8601String(),
                "prj_update_date" => $process->PRO_UPDATE_DATE->toIso8601String(),
                "prj_status"      => $process->PRO_STATUS,
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
                               self::API_TEST_PROJECT . '?filter=' . urlencode($process->PRO_NAME) . '&start=0&limit=1');
        $response->assertStatus(200);
        $response->assertJsonStructure();
        $response->assertJsonFragment([
            [
                "prj_uid"         => $process->PRO_UID,
                "prj_name"        => $process->PRO_NAME,
                "prj_description" => $process->PRO_DESCRIPTION,
                "prj_category"    => $process->PRO_CATEGORY,
                "prj_type"        => $process->PRO_TYPE,
                "prj_create_date" => $process->PRO_CREATE_DATE->toIso8601String(),
                "prj_update_date" => $process->PRO_UPDATE_DATE->toIso8601String(),
                "prj_status"      => $process->PRO_STATUS,
            ]
        ]);
    }

    /**
     * Get the process definition in a json format.
     *
     */
    public function testGetDefinition()
    {
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
            'PRO_CREATE_USER' => $admin->USR_UID
        ]);
        $diagram = factory(Diagram::class)->create([
            'PRO_ID' => $process->PRO_ID,
        ]);
        $activities = factory(Activity::class, 3)->create([
                'PRO_ID'  => $process->PRO_ID
            ])->each(function (Activity $activity) use ($diagram) {
            $activity->createShape($diagram);
        });
        $events = factory(Event::class, 2)->create([
                'PRO_ID' => $process->PRO_ID
            ])->each(function (Event $event) use ($diagram) {
            $event->createShape($diagram);
        });
        $gateway = factory(Gateway::class)->create([
            'PRO_ID' => $process->PRO_ID
        ]);
        $gateway->createShape($diagram);
        factory(Artifact::class, 3)->create([
                'PRO_ID' => $process->PRO_ID
            ])->each(function (Artifact $artifact) use ($diagram) {
            $artifact->createShape($diagram);
        });
        factory(Laneset::class, 3)->create([
                'PRO_ID' => $process->PRO_ID
            ])->each(function (Laneset $laneset) use ($diagram) {
            $laneset->createShape($diagram);
        });
        factory(Lane::class, 3)->create([
                'PRO_ID' => $process->PRO_ID,
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
        $response = $this->api('GET', self::API_TEST_PROJECT . '/' . $process->PRO_UID);
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
        $admin = $this->authenticateAsAdmin();
        // We need a process
        $process = factory(Process::class)->create([
            'PRO_CREATE_USER' => $admin->USR_UID
        ]);
        $diagram = factory(Diagram::class)->create([
            'PRO_ID' => $process->PRO_ID,
        ]);
        $activities = factory(Activity::class, 3)->create([
                'PRO_ID'  => $process->PRO_ID
            ])->each(function (Activity $activity) use ($diagram) {
            $activity->createShape($diagram);
        });
        $events = factory(Event::class, 2)->create([
                'PRO_ID' => $process->PRO_ID
            ])->each(function (Event $event) use ($diagram) {
            $event->createShape($diagram);
        });
        $gateway = factory(Gateway::class)->create([
            'PRO_ID' => $process->PRO_ID
        ]);
        $gateway->createShape($diagram);
        $artifacts = factory(Artifact::class, 3)->create([
                'PRO_ID' => $process->PRO_ID
            ])->each(function (Artifact $artifact) use ($diagram) {
            $artifact->createShape($diagram);
        });
        $lanesets = factory(Laneset::class, 3)->create([
                'PRO_ID' => $process->PRO_ID
            ])->each(function (Laneset $laneset) use ($diagram) {
            $laneset->createShape($diagram);
        });
        $lanes = factory(Lane::class, 3)->create([
                'PRO_ID' => $process->PRO_ID,
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
        $response = $this->api('DELETE', self::API_TEST_PROJECT . '/' . $process->PRO_UID);
        $response->assertStatus(204);
        $this->assertDatabaseMissing($process->getTable(), [
            'PRO_UID' => $process->PRO_UID
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
            'PRO_CREATE_USER' => $admin->USR_UID
        ]);
        $application = factory(Application::class)->create([
            'PRO_ID'  => $process->PRO_ID,
            'PRO_UID' => $process->PRO_UID,
        ]);
        $response = $this->api('DELETE', self::API_TEST_PROJECT . '/' . $process->PRO_UID);
        $response->assertStatus(422);

        //Verify that process was not deleted
        $this->assertDatabaseHas($process->getTable(), [
            'PRO_UID' => $process->PRO_UID
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
