<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Group;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\TaskUser;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class TaskAssigneeManagerTest extends ApiTestCase
{
    use DatabaseTransactions;

    const API_ROUTE = '/api/1.0/process/';
    const DEFAULT_PASS = 'password';

    /**
     * @var User
     */
    protected static $user;
    /**
     * @var Process
     */
    protected static $process;
    /**
     * @var Task
     */
    protected static $activity;
    /**
     * @var Group
     */
    protected static $group;

    /**
     * Create user, task,  process
     */
    private function initProcess(): void
    {
        self::$user = factory(User::class)->create([
            'password' => Hash::make(self::DEFAULT_PASS),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);

        self::$process = factory(Process::class)->create([
            'creator_user_id' => self::$user->id
        ]);

        self::$activity = factory(Task::class)->create([
            'process_id' => self::$process->id
        ]);

        self::$group = factory(Group::class)->create();

        //Assign users to group
        $users = User::all();
        self::$group->users()->attach($users);
    }

    /**
     * Add assignee to task
     */
    public function testStore(): void
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );

        $this->initProcess();
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        $data = [
            'type' => 'OtherType',
            'uid' => '123'
        ];
        //validate non-existent Type user or Group
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/assignee';
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(404);

        $data['type'] = 'user';
        //validate non-existent user
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/assignee';
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(404);

        //validate non-existent group
        $data['type'] = 'group';
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(404);

        //correctly insert assignment user
        $data['type'] = 'user';
        $data['uid'] = self::$user->uid;
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(201);

        //reassigned user exist
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(404);

        //correctly insert assignment user
        $data['type'] = 'group';
        $data['uid'] = self::$group->uid;
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(201);

        //Reassigned group exist
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(404);
    }

    /**
     * List the users and groups assigned to a task.
     *
     * @depends testStore
     */
    public function testAssigneeToTask(): void
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        $this->auth(self::$user->username, self::DEFAULT_PASS);
        $structurePaginate = [
            'data',
            'meta',
        ];

        //Task not belong to process
        $activity = factory(Task::class)->create();
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . $activity->uid . '/assignee';
        $response = $this->api('GET', $url);
        $response->assertStatus(404);

        $assigned = [self::$user->uid->toString(), self::$group->uid];
        //List users and groups
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/assignee';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);
        //verify the user and group assigned
        $this->assertEquals($response->json()['meta']['total'], 2);
        $this->assertContains($response->json()['data'][0]['uid'], $assigned);
        $this->assertContains($response->json()['data'][1]['uid'], $assigned);

        //Filter user
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/assignee?filter=' . self::$user->firstname;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);
        //verify the user and group assigned
        $this->assertLessThanOrEqual(count($response->json()['data']), 1);
        $data = [];
        foreach ($response->json()['data'] as $info) {
            $data[] = $info['uid'];
        }
        $this->assertContains(self::$user->uid->toString(), $data);

        //Filter group
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/assignee?filter=' . self::$group->title;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);
        //verify the user and group assigned
        $this->assertLessThanOrEqual(count($response->json()['data']), 1);
        $data = [];
        foreach ($response->json()['data'] as $info) {
            $data[] = $info['uid'];
        }
        $this->assertContains(self::$group->uid, $data);

        //Filter not exist results
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/assignee?filter=' . 'THERE_ARE_NO_RESULTS';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);
        //verify result
        $this->assertEquals(count($response->json()['data']), 0);
    }

    /**
     * List the users and groups assigned to a task.
     *
     * @depends testStore
     */
    public function testAssigneeToTaskPaged(): void
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        $this->auth(self::$user->username, self::DEFAULT_PASS);
        $structurePaginate = [
            'data',
            'meta',
        ];

        $assigned = [self::$user->uid->toString(), self::$group->uid];

        //List users and groups
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/assignee/paged';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);
        //verify the user and group assigned
        $this->assertEquals($response->json()['meta']['total'], 2);
        $this->assertContains($response->json()['data'][0]['uid'], $assigned);
        $this->assertContains($response->json()['data'][1]['uid'], $assigned);

        //Filter user
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/assignee/paged?filter=' . self::$user->firstname;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);
        //verify the user and group assigned
        $this->assertLessThanOrEqual($response->json()['meta']['total'], 1);
        $data = [];
        foreach ($response->json()['data'] as $info) {
            $data[] = $info['uid'];
        }
        $this->assertContains(self::$user->uid->toString(), $data);

        //Filter group
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/assignee/paged?filter=' . self::$group->title;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);
        //verify the user and group assigned
        $this->assertLessThanOrEqual($response->json()['meta']['total'], 1);
        $data = [];
        foreach ($response->json()['data'] as $info) {
            $data[] = $info['uid'];
        }
        $this->assertContains(self::$group->uid, $data);

        //Filter not exist results
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/assignee/paged?filter=' . 'THERE_ARE_NO_RESULTS';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);
        //verify result
        $this->assertEquals($response->json()['meta']['total'], 0);
    }

    /**
     * Get single information of user or group assignee to activity
     *
     * @depends testStore
     */
    public function testGetInformationAssignee(): void
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        $this->auth(self::$user->username, self::DEFAULT_PASS);
        $structure = [
            'uid',
            'name',
            'lastname',
            'username',
            'type'
        ];

        //Other User row not exist
        $assignee = factory(User::class)->make();
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/assignee/' . $assignee->uid;
        $response = $this->api('GET', $url);
        $response->assertStatus(404);

        //Other Group row not exist
        $assignee = factory(Group::class)->make();
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/assignee/' . $assignee->uid;
        $response = $this->api('GET', $url);
        $response->assertStatus(404);

        //Verify user information
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/assignee/' . self::$user->uid->toString();
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $response->assertJsonStructure($structure);

        //Verify user information
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/assignee/' . self::$group->uid;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $response->assertJsonStructure($structure);
    }

    /**
     * Get single information of user or group assignee to activity
     *
     * @depends testStore
     */
    public function testGetAllInformationAssignee(): void
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        $this->auth(self::$user->username, self::DEFAULT_PASS);
        $structurePaginate = [
            'data',
            'meta',
        ];

        //List All
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/assignee/all';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);
        //verify the user and group assigned
        $this->assertLessThanOrEqual(count($response->json()['data']), 2);

        //Filter user
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/assignee/all?filter=' . self::$user->firstname;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);
        //verify the user and group assigned
        $this->assertLessThanOrEqual(count($response->json()['data']), 1);
        $data = [];
        foreach ($response->json()['data'] as $info) {
            $data[] = $info['uid'];
        }
        $this->assertContains(self::$user->uid->toString(), $data);

        //Filter not exist results
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/assignee/all?filter=' . 'THERE_ARE_NO_RESULTS';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);
        //verify result
        $this->assertLessThanOrEqual(count($response->json()['data']), 0);
    }

    /**
     * List the users and groups available to a task.
     *
     * @depends testStore
     */
    public function testGetAvailable(): void
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        $this->auth(self::$user->username, self::DEFAULT_PASS);
        $structurePaginate = [
            'data',
            'meta',
        ];

        //List All
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/available-assignee';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);
        //verify the user and group assigned
        foreach ($response->json()['data'] as $available) {
            $this->assertNotEquals($available['uid'], self::$user->uid->toString());
            $this->assertNotEquals($available['uid'], self::$group->uid);
        }

        //Filter user
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/available-assignee?filter=' . self::$user->firstname;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);
        //verify the user and group assigned
        foreach ($response->json()['data'] as $available) {
            $this->assertNotEquals($available['uid'], self::$user->uid);
        }

        //Filter group
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/available-assignee?filter=' . self::$group->title;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);
        //verify the user and group assigned
        foreach ($response->json()['data'] as $available) {
            $this->assertNotEquals($available['uid'], self::$group->uid);
        }

        //Filter not exist results
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/available-assignee?filter=' . 'THERE_ARE_NO_RESULTS';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);
        //verify result
        $this->assertEquals(count($response->json()['data']), 0);
    }

    /**
     * LGet a page of the available users and groups which may be assigned to a task.
     *
     * @depends testStore
     */
    public function testGetAvailablePaged(): void
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        $this->auth(self::$user->username, self::DEFAULT_PASS);
        $structurePaginate = [
            'data',
            'meta',
        ];

        //List All
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/available-assignee/paged';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);
        //verify the user and group assigned
        foreach ($response->json()['data'] as $available) {
            $this->assertNotEquals($available['uid'], self::$user->uid->toString());
            $this->assertNotEquals($available['uid'], self::$group->uid);
        }

        //Filter user
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/available-assignee/paged?filter=' . self::$user->firstname;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);
        //verify the user and group assigned
        foreach ($response->json()['data'] as $available) {
            $this->assertNotEquals($available['uid'], self::$user->uid->toString());
        }

        //Filter group
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/available-assignee/paged?filter=' . self::$group->title;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);
        //verify the user and group assigned
        foreach ($response->json()['data'] as $available) {
            $this->assertNotEquals($available['uid'], self::$group->uid);
        }

        //Filter not exist results
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/available-assignee/paged?filter=' . 'THERE_ARE_NO_RESULTS';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);
        //verify result
        $this->assertEquals(count($response->json()['data']), 0);
    }

    /**
     * Remove assignee of Activity
     *
     * @depends testStore
     */
    public function testRemoveAssignee(): void
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //Other User row not exist
        $assignee = factory(User::class)->make();
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/assignee/' . $assignee->uid;
        $response = $this->api('DELETE', $url);
        $response->assertStatus(404);

        //Other Activity row not exist
        $activity = factory(Task::class)->make();
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . $activity->uid . '/assignee/' . $assignee->uid;
        $response = $this->api('DELETE', $url);
        $response->assertStatus(404);

        //Other Group row not exist
        $assignee = factory(Group::class)->make();
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/assignee/' . $assignee->uid;
        $response = $this->api('DELETE', $url);
        $response->assertStatus(404);

        //delete user successfully
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/assignee/' . self::$user->uid->toString();
        $response = $this->api('DELETE', $url);
        $response->assertStatus(200);

        //delete group successfully
        $url = self::API_ROUTE . self::$process->uid . '/activity/' . self::$activity->uid . '/assignee/' . self::$group->uid;
        $response = $this->api('DELETE', $url);
        $response->assertStatus(200);

    }

}
