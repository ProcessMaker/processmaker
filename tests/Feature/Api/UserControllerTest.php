<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Cache;
use ProcessMaker\Models\Permission;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RequestHelper {
        RequestHelper::setUp as requestHelperSetUp;
    }

    public $withPermissions = true;

    protected function setUp(): void
    {
        $this->requestHelperSetUp();

        $this->user->is_administrator = false;
        $this->user->save();
        $this->user->permissions()->detach();
        Cache::forget("user_{$this->user->id}_manager");
        Cache::forget("user_{$this->user->id}_permissions");
        $this->user->invalidatePermissionCache();
    }

    public function testUsersIndexRequiresPermission(): void
    {
        self::assertFalse($this->user->hasPermission('view-users'));

        $response = $this->apiCall('GET', route('api.users.index'));

        $response->assertStatus(403);
    }

    public function testUsersIndexSucceedsWithPermission(): void
    {
        $this->user->permissions()->attach(Permission::byName('view-users'));
        $this->user->refresh();
        $this->user->invalidatePermissionCache();

        $response = $this->apiCall('GET', route('api.users.index'));

        $response->assertOk();
    }

    public function testUsersTaskCountRequiresPermission(): void
    {
        self::assertFalse($this->user->hasPermission('view-users'));

        $response = $this->apiCall('GET', route('api.users.users_task_count'));

        $response->assertStatus(403);
    }

    public function testUsersTaskCountSucceedsWithPermission(): void
    {
        $this->user->permissions()->attach(Permission::byName('view-users'));
        $this->user->refresh();
        $this->user->invalidatePermissionCache();

        $response = $this->apiCall('GET', route('api.users.users_task_count'));

        $response->assertOk();
    }
}
