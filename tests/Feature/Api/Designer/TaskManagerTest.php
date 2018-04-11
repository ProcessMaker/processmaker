<?php

namespace Tests\Feature\Api\Designer;

use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class TaskManagerTest extends ApiTestCase
{
    const API_ROUTE = '/api/1.0/project/';

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
        $task = factory(Task::class)->create([
            'PRO_UID' => $process->PRO_UID
        ]);
        $this->assertNotNull($task);
        $this->assertNotNull($task->TAS_UID);
        return $task;
    }

    /**
     * create User
     * @return User
     */
    public function testCreateUser()
    {
        $user = factory(User::class)->create([
            'USR_PASSWORD' => Hash::make('password'),
            'USR_ROLE' => Role::PROCESSMAKER_ADMIN
        ]);
        $this->assertNotNull($user);
        $this->assertNotNull($user->USR_UID);
        return $user;
    }

    /**
     * @param Process $process
     * @param Task $task
     * @package User $user
     *
     * @depends testCreateProcess
     * @depends testCreateTask
     * @depends testCreateUser
     */
    public function testStore(Process $process, Task $task, User $user)
    {
        $this->auth($user->USR_USERNAME, 'password');

        $data = [
            'aas_type' => 'user',
            'ass_uid' => '123'
        ];
        //validate non-existent user
        $url = self::API_ROUTE . $process->PRO_UID . '/activity/' . $task->TAS_UID . '/assignee';
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(400);

        //validate non-existent group
        $data['aas_type'] = 'group';
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(400);

        //validate user
        $data['aas_type'] = 'user';
        $data['aas_uid'] = $user->getIdentifier();
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(200);


    }

}
