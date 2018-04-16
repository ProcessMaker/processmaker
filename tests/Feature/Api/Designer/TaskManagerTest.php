<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Group;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\Task;
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
    public function testCreateProcess()
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
    public function testCreateTask(Process $process)
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
    public function testCreateUser()
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
        /*$group->users()->create(factory(User::class)->create([
            'USR_PASSWORD' => Hash::make(self::DEFAULT_PASS),
            'USR_ROLE' => Role::PROCESSMAKER_ADMIN
        ])->toArray());*/
        return $group;
    }

    /**
     * @param Process $process
     * @param Task $activity
     * @package User $user
     *
     * @depends testCreateProcess
     * @depends testCreateTask
     * @depends testCreateUser
     * @depends testCreateGroup
     */
    public function testStore(Process $process, Task $activity, User $user, Group $group)
    {
        $this->auth($user->USR_USERNAME, 'password');

        $data = [
            'aas_type' => 'user',
            'aas_uid' => '123'
        ];
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
        $response->assertStatus(200);

        //reassigned user exist
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(400);

        //correctly insert assignment user
        $data['aas_type'] = 'group';
        $data['aas_uid'] = $group->GRP_UID;
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(200);

        //Reassigned group exist
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(400);


    }

}
