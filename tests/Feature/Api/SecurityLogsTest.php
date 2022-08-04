<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\SecurityLog;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\AuthServiceProvider;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class SecurityLogsTest extends TestCase
{
    use RequestHelper;

    protected function withUserSetup()
    {
        $this->user->is_administrator = false;
        $this->user->save();

        // Seed our tables.
        Artisan::call('db:seed', ['--class' => 'PermissionSeeder']);

        // Reboot our AuthServiceProvider. This is necessary so that it can
        // pick up the new permissions and setup gates for each of them.
        $asp = new AuthServiceProvider(app());
        $asp->boot();
    }

    public function setUpWithPersonalAccessClient()
    {
        $this->withPersonalAccessClient();
    }

    /**
     * Attempt to access security logs
     */
    public function testAccessSecurityLogsApi()
    {
        $response = $this->apiCall('GET', '/security-logs');
        $response->assertStatus(403);

        $permission = Permission::byName('view-security-logs');
        $this->user->permissions()->attach($permission->id);
        $this->user->refresh();
        $this->flushSession();

        $response = $this->apiCall('GET', '/security-logs');
        $response->assertStatus(200);
    }

    public function testSearchSecurityLogsApi()
    {
        $permission = Permission::byName('view-security-logs');
        $this->user->permissions()->attach($permission->id);
        $this->user->refresh();

        // Create security logs for two users.
        factory(SecurityLog::class)->create([
            'event' => 'login',
            'user_id' => $this->user->id,
            'meta' => [
                'os' => [
                    'name' => 'OS X',
                ],
                'browser' => [
                    'name' => 'Firefox',
                ],
            ],
        ]);
        factory(SecurityLog::class)->create([
            'event' => 'login',
            'user_id' => $this->user->id,
            'meta' => [
                'os' => [
                    'name' => 'OS X',
                ],
                'browser' => [
                    'name' => 'Chrome',
                ],
            ],
        ]);
        $anotherUser = factory(User::class)->create();
        factory(SecurityLog::class)->create([
            'event' => 'login',
            'user_id' => $anotherUser->id,
            'meta' => [
                'os' => [
                    'name' => 'OS X',
                ],
                'browser' => [
                    'name' => 'Firefox',
                ],
            ],
        ]);

        // Test that the results obtained are from the user in session.
        $response = $this->apiCall('GET', '/security-logs', [
            'pmql' => "user_id={$this->user->id}",
            'filter' => 'firefox',
        ]);
        $response->assertStatus(200);
        $results = $response->getData()->data;
        $this->assertCount(1, $results);

        // Test a PMQL search query.
        $response = $this->apiCall('GET', '/security-logs', [
            'pmql' => 'user_id='.$this->user->id.' AND (event = "login")',
            'filter' => '',
        ]);
        $response->assertStatus(200);
        $results = $response->getData()->data;
        $this->assertCount(2, $results);
    }
}
