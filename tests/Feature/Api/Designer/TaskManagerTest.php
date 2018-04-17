<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\WithFaker;
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
            'PRO_UID' => $process->PRO_UID
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
        $response->assertStatus(400);

        $data['aas_type'] = 'user';
        //validate non-existent user
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee';
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(400);

        //validate non-existent group
        $data['aas_type'] = 'group';
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(400);

        //correctly insert assignment user
        $data['aas_type'] = 'user';
        $data['aas_uid'] = $user->getIdentifier();
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(201);

        //reassigned user exist
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(400);

        //correctly insert assignment user
        $data['aas_type'] = 'group';
        $data['aas_uid'] = $group->GRP_UID;
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(201);

        //Reassigned group exist
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(400);
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
        $response->assertStatus(400);

        //Other Group row not exist
        $userMake = factory(Group::class)->make();
        $assignee->USR_UID = $userMake->GRP_UID;
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $activity->TAS_UID . '/assignee/' . $assignee->USR_UID;
        $response = $this->api('GET', $url);
        $response->assertStatus(400);

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
        $response->assertStatus(400);

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
        $response->assertStatus(400);

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
