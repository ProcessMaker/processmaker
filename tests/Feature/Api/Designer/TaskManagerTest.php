<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Group;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\TaskUser;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class TaskManagerTest extends ApiTestCase
{
    const API_ROUTE = '/api/1.0/project/';
    const DEFAULT_PASS = 'password';

    /**
     * Create process
     * @return Process
     */
    public function testCreateProcess(): Process
    {
        $process = factory(Process::class)->create();
        $this->assertNotNull($process);
        $this->assertNotNull($process->PRO_UID);
        return $process;
    }

    /**
     * Create Task
     * @param Process $process
     *
     * @depends testCreateProcess
     * @return Task
     */
    public function testCreateTask(Process $process): Task
    {
        $activity = factory(Task::class)->create([
            'PRO_UID' => $process->PRO_UID,
            'PRO_ID' => $process->PRO_ID
        ]);
        $this->assertNotNull($activity);
        $this->assertNotNull($activity->TAS_UID);
        $this->assertNotNull($activity->TAS_ID);
        return $activity;
    }

    /**
     * create User
     * @return User
     */
    public function testCreateUser(): User
    {
        $user = factory(User::class)->create([
            'USR_PASSWORD' => Hash::make(self::DEFAULT_PASS),
            'USR_ROLE' => Role::PROCESSMAKER_ADMIN
        ]);
        $this->assertNotNull($user);
        $this->assertNotNull($user->USR_UID);
        $this->assertNotNull($user->USR_ID);
        return $user;
    }

    /**
     * create a group and assign users in it.
     *
     * @return Group
     */
    public function testCreateGroup(): Group
    {
        $group = factory(Group::class)->create([
            'GRP_STATUS' => 'ACTIVE',
            'GRP_LDAP_DN' => '',
            'GRP_UX' => 'NORMAL'
        ]);
        $this->assertNotNull($group);
        $this->assertNotNull($group->GRP_UID);
        $this->assertNotNull($group->GRP_ID);

        //Assign users to group
        $users = User::all('USR_ID')->toArray();
        $faker = Faker::create();
        $group->users()->attach($faker->randomElements($users, $faker->randomDigitNotNull));

        return $group;
    }

    /**
     * Add assignee to task
     *
     * @param Process $process
     * @param Task $activity
     * @param User $user
     * @param Group $group
     *
     * @depends testCreateProcess
     * @depends testCreateTask
     * @depends testCreateUser
     * @depends testCreateGroup
     */
    public function testStore(Process $process, Task $activity, User $user, Group $group): void
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);

        $data = [
            'aas_type' => 'OtherType',
            'aas_uid' => '123'
        ];
        //validate non-existent Type user or Group
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee';
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(404);

        $data['aas_type'] = 'user';
        //validate non-existent user
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee';
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(404);

        //validate non-existent group
        $data['aas_type'] = 'group';
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(404);

        //correctly insert assignment user
        $data['aas_type'] = 'user';
        $data['aas_uid'] = $user->getIdentifier();
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(201);

        //reassigned user exist
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(404);

        //correctly insert assignment user
        $data['aas_type'] = 'group';
        $data['aas_uid'] = $group->GRP_UID;
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(201);

        //Reassigned group exist
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(404);
    }

    /**
     * List the users and groups assigned to a task.
     *
     * @param Process $process
     * @param Task $activity
     * @param User $user
     * @param Group $group
     *
     * @depends testCreateProcess
     * @depends testCreateTask
     * @depends testCreateUser
     * @depends testCreateGroup
     * @depends testStore
     */
    public function testAssigneeToTask(Process $process, Task $activity, User $user, Group $group): void
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);
        $structurePaginate = [
            'current_page',
            'data',
            'first_page_url',
            'from',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
        ];

        //List users and groups
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);
        //verify the user and group assigned
        $this->assertEquals(count($response->json()['data']), 2);
        $this->assertEquals($response->json()['data'][0]['aas_uid'], $user->USR_UID);
        $this->assertEquals($response->json()['data'][1]['aas_uid'], $group->GRP_UID);

        //Filter user
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee?filter='. $user->USR_FIRSTNAME;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);
        //verify the user and group assigned
        $this->assertLessThanOrEqual(count($response->json()['data']), 1);
        $data = [];
        foreach ($response->json()['data'] as $info) {
            $data[] = $info['aas_uid'];
        }
        $this->assertContains( $user->USR_UID, $data);

        //Filter group
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee?filter='. $group->GRP_TITLE;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);
        //verify the user and group assigned
        $this->assertLessThanOrEqual(count($response->json()['data']), 1);
        $data = [];
        foreach ($response->json()['data'] as $info) {
            $data[] = $info['aas_uid'];
        }
        $this->assertContains( $group->GRP_UID, $data);

        //Filter not exist results
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee?filter='. 'THERE_ARE_NO_RESULTS';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);
        //verify result
        $this->assertEquals(count($response->json()['data']), 0);
    }

    /**
     * List the users and groups assigned to a task.
     *
     * @param Process $process
     * @param Task $activity
     * @param User $user
     * @param Group $group
     *
     * @depends testCreateProcess
     * @depends testCreateTask
     * @depends testCreateUser
     * @depends testCreateGroup
     * @depends testStore
     */
    public function testAssigneeToTaskPaged(Process $process, Task $activity, User $user, Group $group): void
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);
        $structurePaginate = [
            'current_page',
            'data',
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total'
        ];

        //List users and groups
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee/paged';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);
        //verify the user and group assigned
        $this->assertEquals($response->json()['total'], 2);
        $this->assertEquals($response->json()['data'][0]['aas_uid'], $user->USR_UID);
        $this->assertEquals($response->json()['data'][1]['aas_uid'], $group->GRP_UID);

        //Filter user
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee/paged?filter='. $user->USR_FIRSTNAME;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);
        //verify the user and group assigned
        $this->assertLessThanOrEqual($response->json()['total'], 1);
        $data = [];
        foreach ($response->json()['data'] as $info) {
            $data[] = $info['aas_uid'];
        }
        $this->assertContains($user->USR_UID, $data);

        //Filter group
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee/paged?filter='. $group->GRP_TITLE;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);
        //verify the user and group assigned
        $this->assertLessThanOrEqual($response->json()['total'], 1);
        $data = [];
        foreach ($response->json()['data'] as $info) {
            $data[] = $info['aas_uid'];
        }
        $this->assertContains( $group->GRP_UID, $data);

        //Filter not exist results
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee/paged?filter='. 'THERE_ARE_NO_RESULTS';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);
        //verify result
        $this->assertEquals($response->json()['total'], 0);
    }

    /**
     * Get single information of user or group assignee to activity
     *
     * @param Process $process
     * @param Task $activity
     * @param User $user
     * @param Group $group
     *
     * @depends testCreateProcess
     * @depends testCreateTask
     * @depends testCreateUser
     * @depends testCreateGroup
     * @depends testStore
     */
    public function testGetInformationAssignee(Process $process, Task $activity, User $user, Group $group): void
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);

        $assignee = new TaskUser();

        //Other User row not exist
        $userMake = factory(User::class)->make();
        $assignee->USR_UID = $userMake->USR_UID;
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee/' . $assignee->USR_UID;
        $response = $this->api('GET', $url);
        $response->assertStatus(404);

        //Other Group row not exist
        $userMake = factory(Group::class)->make();
        $assignee->USR_UID = $userMake->GRP_UID;
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee/' . $assignee->USR_UID;
        $response = $this->api('GET', $url);
        $response->assertStatus(404);

        //Verify user information
        $assignee->USR_UID = $user->USR_UID;
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee/' . $assignee->USR_UID;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $response->assertJsonstructure([
            'aas_uid',
            'aas_name',
            'aas_lastname',
            'aas_username',
            'aas_type'
        ]);

        //Verify user information
        $assignee->USR_UID = $group->GRP_UID;
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee/' . $assignee->USR_UID;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $response->assertJsonstructure([
            'aas_uid',
            'aas_name',
            'aas_lastname',
            'aas_username',
            'aas_type'
        ]);
    }

    /**
     * Get single information of user or group assignee to activity
     *
     * @param Process $process
     * @param Task $activity
     * @param User $user
     * @param Group $group
     *
     * @depends testCreateProcess
     * @depends testCreateTask
     * @depends testCreateUser
     * @depends testCreateGroup
     * @depends testStore
     */
    public function testGetAllInformationAssignee(Process $process, Task $activity, User $user, Group $group): void
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);
        $structurePaginate = [
            'current_page',
            'data',
            'first_page_url',
            'from',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
        ];

        //List All
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee/all';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);
        //verify the user and group assigned
        $this->assertLessThanOrEqual(count($response->json()['data']), 2);

        //Filter user
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee/all?filter='. $user->USR_FIRSTNAME;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);
        //verify the user and group assigned
        $this->assertLessThanOrEqual(count($response->json()['data']), 1);
        $data = [];
        foreach ($response->json()['data'] as $info) {
            $data[] = $info['aas_uid'];
        }
        $this->assertContains( $user->USR_UID, $data);

        //Filter not exist results
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee/all?filter='. 'THERE_ARE_NO_RESULTS';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);
        //verify result
        $this->assertLessThanOrEqual(count($response->json()['data']), 0);
    }

    /**
     * List the users and groups available to a task.
     *
     * @param Process $process
     * @param Task $activity
     * @param User $user
     * @param Group $group
     *
     * @depends testCreateProcess
     * @depends testCreateTask
     * @depends testCreateUser
     * @depends testCreateGroup
     * @depends testStore
     */
    public function testGetAvailable(Process $process, Task $activity, User $user, Group $group): void
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);
        $structurePaginate = [
            'current_page',
            'data',
            'first_page_url',
            'from',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
        ];

        //List All
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/available-assignee';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);
        //verify the user and group assigned
        foreach ($response->json()['data'] as $available) {
            $this->assertNotEquals($available['aas_uid'], $user->USR_UID);
            $this->assertNotEquals($available['aas_uid'], $group->GRP_UID);
        }

        //Filter user
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/available-assignee?filter='. $user->USR_FIRSTNAME;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);
        //verify the user and group assigned
        foreach ($response->json()['data'] as $available) {
            $this->assertNotEquals($available['aas_uid'], $user->USR_UID);
        }

        //Filter group
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/available-assignee?filter='. $group->GRP_TITLE;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);
        //verify the user and group assigned
        foreach ($response->json()['data'] as $available) {
            $this->assertNotEquals($available['aas_uid'], $group->GRP_UID);
        }

        //Filter not exist results
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/available-assignee?filter='. 'THERE_ARE_NO_RESULTS';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);
        //verify result
        $this->assertEquals(count($response->json()['data']), 0);
    }

    /**
     * LGet a page of the available users and groups which may be assigned to a task.
     *
     * @param Process $process
     * @param Task $activity
     * @param User $user
     * @param Group $group
     *
     * @depends testCreateProcess
     * @depends testCreateTask
     * @depends testCreateUser
     * @depends testCreateGroup
     * @depends testStore
     */
    public function testGetAvailablePaged(Process $process, Task $activity, User $user, Group $group): void
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);
        $structurePaginate = [
            'current_page',
            'data',
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total'
        ];

        //List All
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/available-assignee/paged';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);
        //verify the user and group assigned
        foreach ($response->json()['data'] as $available) {
            $this->assertNotEquals($available['aas_uid'], $user->USR_UID);
            $this->assertNotEquals($available['aas_uid'], $group->GRP_UID);
        }

        //Filter user
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/available-assignee/paged?filter='. $user->USR_FIRSTNAME;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);
        //verify the user and group assigned
        foreach ($response->json()['data'] as $available) {
            $this->assertNotEquals($available['aas_uid'], $user->USR_UID);
        }

        //Filter group
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/available-assignee/paged?filter='. $group->GRP_TITLE;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);
        //verify the user and group assigned
        foreach ($response->json()['data'] as $available) {
            $this->assertNotEquals($available['aas_uid'], $group->GRP_UID);
        }

        //Filter not exist results
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/available-assignee/paged?filter='. 'THERE_ARE_NO_RESULTS';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);
        //verify result
        $this->assertEquals(count($response->json()['data']), 0);
    }

    /**
     * Remove assignee of Activity
     *
     * @param Process $process
     * @param Task $activity
     * @param User $user
     * @param Group $group
     *
     * @depends testCreateProcess
     * @depends testCreateTask
     * @depends testCreateUser
     * @depends testCreateGroup
     * @depends testStore
     */
    public function testRemoveAssignee(Process $process, Task $activity, User $user, Group $group): void
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);

        $assignee = new TaskUser();

        //Other User row not exist
        $userMake = factory(User::class)->make();
        $assignee->USR_UID = $userMake->USR_UID;
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee/' . $assignee->USR_UID;
        $response = $this->api('DELETE', $url);
        $response->assertStatus(404);

        //Other Activity row not exist
        $activityMake = factory(Task::class)->make();
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activityMake->TAS_UID . '/assignee/' . $assignee->USR_UID;
        $response = $this->api('DELETE', $url);
        $response->assertStatus(404);

        //Other Group row not exist
        $groupMake = factory(Group::class)->make();
        $assignee->USR_UID = $groupMake->GRP_UID;
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee/' . $assignee->USR_UID;
        $response = $this->api('DELETE', $url);
        $response->assertStatus(404);

        //delete user successfully
        $assignee->USR_UID = $user->USR_UID;
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee/' . $assignee->USR_UID;
        $response = $this->api('DELETE', $url);
        $response->assertStatus(200);

        //delete group successfully
        $assignee->USR_UID = $group->GRP_UID;
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee/' . $assignee->USR_UID;
        $response = $this->api('DELETE', $url);
        $response->assertStatus(200);

    }

}
