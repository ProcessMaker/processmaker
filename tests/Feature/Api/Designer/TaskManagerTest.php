<?php

namespace Tests\Feature\Api\Designer;

use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Process;
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
        return factory(Process::class)->create();
    }

    /**
     * Create Task
     * @return Task
     */
    public function testCreateTask()
    {
        return factory(Task::class)->create();
    }

    /**
     * create User
     * @return User
     */
    public function testCreateUser()
    {
        return factory(User::class)->create([
            'USR_PASSWORD' => Hash::make('password'),
            'USR_ROLE'     => Role::PROCESSMAKER_ADMIN
        ]);
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

    }

}