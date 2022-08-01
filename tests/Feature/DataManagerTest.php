<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use PermissionSeeder;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\AuthServiceProvider;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Test edit data
 */
class DataManagerTest extends TestCase
{
    use RequestHelper;

    protected function setUp(): void
    {
        parent::setUp();

        // Creates an admin user
        $this->admin = factory(User::class)->create([
            'password' => Hash::make('password'),
            'is_administrator' => true,
        ]);

        // Creates an user
        $this->user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'is_administrator' => false,
        ]);

        // Create a group
        $this->group = factory(Group::class)->create(['name' => 'group']);
        factory(GroupMember::class)->create([
            'member_id' => $this->user->id,
            'member_type' => User::class,
            'group_id' => $this->group->id,
        ]);

        //Run the permission seeder
        (new PermissionSeeder)->run();

        // Reboot our AuthServiceProvider. This is necessary so that it can
        // pick up the new permissions and setup gates for each of them.
        $asp = new AuthServiceProvider(app());
        $asp->boot();
    }

    /**
     * Verify the magic variables for a valid request token
     */
    public function testDataForAValidRequestToken()
    {
        $manager = new DataManager();
        $token = factory(ProcessRequestToken::class)->create();
        $data = $manager->getData($token);
        $user = $token->user;
        $request = $token->processRequest;
        $this->assertEquals($user->id, $data['_user']['id']);
        $this->assertEquals($request->id, $data['_request']['id']);
    }

    /**
     * Verify the magic variables for a valid webentry screen
     */
    public function testDataForAStartEventWebentry()
    {
        $manager = new DataManager();
        $user = $this->user;
        \Auth::login($this->user);
        $data = $manager->getData(null);
        $this->assertEquals($user->id, $data['_user']['id']);
    }
}
