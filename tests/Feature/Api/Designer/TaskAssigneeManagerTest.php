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

    const API_TEST_ASSIGNEE = '/api/1.0/process/';
    const DEFAULT_PASS = 'password';

    /**
     * @var User
     */
    protected $user;
    /**
     * @var Process
     */
    protected $process;
    /**
     * @var Task
     */
    protected $activity;
    /**
     * @var Group
     */
    protected $group;

    const STRUCTURE = [
        'uid',
        'name',
        'lastname',
        'username',
        'type'
    ];

    /**
     * Create user, task,  process
     */
    protected function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create([
            'password' => Hash::make(self::DEFAULT_PASS),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);

        $this->auth($this->user->username, self::DEFAULT_PASS);

        $this->process = factory(Process::class)->create([
            'user_id' => $this->user->id
        ]);

        $this->activity = factory(Task::class)->create([
            'process_id' => $this->process->id
        ]);

        $this->group = factory(Group::class)->create();
    }

    /**
     * Test if exists parameter type
     */
    public function testStoreNotExistType()
    {
        //validate non-existent Type definided
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/assignee';
        $response = $this->api('POST', $url, [
            'type' => 'OtherType',
            'uid' => '123'
        ]);
        $response->assertStatus(404);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * User not exist
     */
    public function testStoreNotExistUser()
    {
        //validate non-existent Type user
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/assignee';
        $response = $this->api('POST', $url, [
            'type' => 'user',
            'uid' => '123'
        ]);
        $response->assertStatus(404);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Group not exist
     */
    public function testStoreNotExistGroup()
    {
        //validate non-existent Type group
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/assignee';
        $response = $this->api('POST', $url, [
            'type' => 'group',
            'uid' => '123'
        ]);
        $response->assertStatus(404);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Assignee correctly user
     */
    public function testStoreUser()
    {
        //validate non-existent Type user or group
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/assignee';
        $response = $this->api('POST', $url, [
            'type' => 'user',
            'uid' => $this->user->uid
        ]);
        $response->assertStatus(201);
    }

    /**
     * Assignee correctly Group
     */
    public function testStoreGroup()
    {
        //validate non-existent Type user or group
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/assignee';
        $response = $this->api('POST', $url, [
            'type' => 'group',
            'uid' => $this->group->uid
        ]);
        $response->assertStatus(201);
    }

    /**
     * User already assigned
     */
    public function testStoreUserAlreadyAssigned()
    {
        factory(TaskUser::class, 'user')->create([
            'task_id' => $this->activity->id,
            'user_id' => $this->user->id
        ]);
        //validate non-existent Type user or group
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/assignee';
        $response = $this->api('POST', $url, [
            'type' => 'user',
            'uid' => $this->user->uid
        ]);
        $response->assertStatus(404);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Group already assigned
     */
    public function testStoreGroupAlreadyAssigned()
    {
        factory(TaskUser::class, 'group')->create([
            'task_id' => $this->activity->id,
            'user_id' => $this->group->id
        ]);
        //validate non-existent Type user or group
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/assignee';
        $response = $this->api('POST', $url, [
            'type' => 'group',
            'uid' => $this->group->uid
        ]);
        $response->assertStatus(404);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * The task not belongs to process.
     */
    public function testGetTriggerNotBelongToProcess()
    {
        //load assignee
        $activity = factory(Task::class)->create();
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $activity->uid . '/assignee';
        $response = $this->api('GET', $url);
        //Validate the answer is incorrect
        $response->assertStatus(404);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * List users and groups assignee
     */
    public function testGetAllAssignee()
    {
        //load assignee
        $total = Faker::create()->randomDigitNotNull;
        factory(TaskUser::class, $total)->create([
            'task_id' => $this->activity->id
        ]);
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/assignee';
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);

        //verify count of data
        $this->assertEquals($total, $response->original->meta->total);
        //Verify the structure
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * Search a user assignee
     */
    public function testFilterUserAssigned()
    {
        $filter = 'User Filter';
        factory(TaskUser::class, 'user')->create([
            'task_id' => $this->activity->id,
            'user_id' => factory(User::class)->create(['firstname' => $filter])->id
        ]);
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/assignee?filter=' . urlencode($filter);
        $response = $this->api('GET', $url);
        $response->assertStatus(200);

        //verify count of data
        $this->assertEquals(1, $response->original->meta->total);
        //Verify the structure
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * Search a Group assignee
     */
    public function testFilterGroupAssigned()
    {
        $filter = 'Group Filter';
        factory(TaskUser::class, 'group')->create([
            'task_id' => $this->activity->id,
            'user_id' => factory(Group::class)->create(['title' => $filter])->id
        ]);
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/assignee?filter=' . urlencode($filter);
        $response = $this->api('GET', $url);
        $response->assertStatus(200);

        //verify count of data
        $this->assertEquals(1, $response->original->meta->total);
        //Verify the structure
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * Search without results
     */
    public function testFilterWithoutResult()
    {
        //Filter not exist results
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/assignee?filter=' . 'THERE_ARE_NO_RESULTS';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $this->assertEquals(0, $response->original->meta->total);
    }

    /**
     * Load information of user not exists
     */
    public function testGetInformationUserNoExists()
    {
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/assignee/' . factory(User::class)->make()->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is incorrect
        $response->assertStatus(404);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Load information of Group not exists
     */
    public function testGetInformationGroupNoExists()
    {
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/assignee/' . factory(Group::class)->make()->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is incorrect
        $response->assertStatus(404);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Load information of user assignee
     */
    public function testGetInformationUserAssignee()
    {
        factory(TaskUser::class, 'user')->create([
            'task_id' => $this->activity->id,
            'user_id' => $this->user->id
        ]);
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/assignee/' . $this->user->uid;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Load information of Group assignee
     */
    public function testGetInformationGroupAssignee()
    {
        factory(TaskUser::class, 'group')->create([
            'task_id' => $this->activity->id,
            'user_id' => $this->group->id
        ]);
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/assignee/' . $this->group->uid;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * List users assignee
     */
    public function testGetInformationAllAssignee()
    {
        //load assignee
        $total = Faker::create()->randomDigitNotNull;
        $previous = TaskUser::where('task_id', $this->activity->id)->count() + 1;
        factory(TaskUser::class, 'user', $total)->create([
            'task_id' => $this->activity->id
        ]);
        $group = factory(Group::class)->create();

        $group->users()->attach(factory(User::class, $total)->create());
        factory(TaskUser::class, 'group')->create([
            'task_id' => $this->activity->id,
            'user_id' => $group->id
        ]);
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/assignee/all';
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);

        //Verify the structure
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * Search a user information assignee
     */
    public function testFilterInformationUserAssigned()
    {
        $filter = 'User Filter';
        factory(TaskUser::class, 'user', 10)->create([
            'task_id' => $this->activity->id,
        ]);
        factory(TaskUser::class, 'user')->create([
            'task_id' => $this->activity->id,
            'user_id' => factory(User::class)->create(['firstname' => $filter])->id
        ]);
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/assignee/all?filter=' . urlencode($filter);
        $response = $this->api('GET', $url);
        $response->assertStatus(200);

        //verify count of data
        $this->assertEquals(1, $response->original->meta->total);
        //Verify the structure
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * Search without results
     */
    public function testInformationFilterWithoutResult()
    {
        //Filter not exist results
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/assignee/all?filter=' . 'THERE_ARE_NO_RESULTS';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $this->assertEquals(0, $response->original->meta->total);
    }

    /**
     * Search a user's information assignee
     */
    public function testUsersGroupsAvailable()
    {
        factory(TaskUser::class)->create([
            'task_id' => $this->activity->id
        ]);
        factory(Group::class, 10 - Group::All()->count())->create();
        factory(User::class, 10 - User::All()->count())->create();
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/available-assignee';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);

        //verify count of data
        $this->assertEquals(19, $response->original->meta->total);
        //Verify the structure
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * Search a user information assignee
     */
    public function testUserAvailable()
    {
        factory(Group::class, 10 - Group::All()->count())->create();
        factory(User::class, 10 - User::All()->count())->create();
        $filter = 'Test User Available';
        factory(User::class)->create([
            'firstname' => $filter
        ]);
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/available-assignee?filter=' . urlencode($filter);
        $response = $this->api('GET', $url);
        $response->assertStatus(200);

        //verify count of data
        $this->assertEquals(1, $response->original->meta->total);
        //Verify the structure
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * Search a Group information assignee
     */
    public function testGroupAvailable()
    {
        factory(Group::class, 10 - Group::All()->count())->create();
        factory(User::class, 10 - User::All()->count())->create();
        $filter = 'Test Group Available';
        factory(Group::class)->create([
            'title' => $filter
        ]);
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/available-assignee?filter=' . urlencode($filter);
        $response = $this->api('GET', $url);
        $response->assertStatus(200);

        //verify count of data
        $this->assertEquals(1, $response->original->meta->total);
        //Verify the structure
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * Remove assignee of Activity
     */
    public function testRemoveNotExistsAssignee()
    {
        //Other User row not exist
        $assignee = factory(User::class)->make();
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/assignee/' . $assignee->uid;
        $response = $this->api('DELETE', $url);
        $response->assertStatus(404);
    }

    /**
     * Delete User assignee in process
     */
    public function testDeleteUserAssignee()
    {
        factory(TaskUser::class, 'user')->create([
            'task_id' => $this->activity->id,
            'user_id' => $this->user->id
        ]);
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/assignee/' . $this->user->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(204);
    }

    /**
     * Delete Group assignee in process
     */
    public function testDeleteGroupAssignee()
    {
        factory(TaskUser::class, 'group')->create([
            'task_id' => $this->activity->id,
            'user_id' => $this->group->id
        ]);
        $url = self::API_TEST_ASSIGNEE . $this->process->uid . '/activity/' . $this->activity->uid . '/assignee/' . $this->group->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(204);
    }

}