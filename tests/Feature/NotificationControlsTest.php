<?php

namespace Tests\Feature;

use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Request;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Notification;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\ProcessNotificationSetting;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class NotificationControlsTest extends TestCase
{
    use RequestHelper;

    public $withPermissions = true;

    /**
     * Test that we receive notifications when they are enabled
     *
     * @return void
     */
    public function testRequestWithNotifications()
    {
        ScriptExecutor::setTestConfig('php');
        
        // Create a user
        $adminUser = factory(User::class)->create([
            'username' => 'admin',
            'is_administrator' => true,
        ]);

        // Seed the processes table.
        Artisan::call('db:seed', ['--class' => 'ProcessSeeder']);

        // Assert that our database has the process we need
        $this->assertDatabaseHas('processes', ['name' => 'Leave Absence Request']);

        // Get the process we'll be testing on
        $process = Process::where('name', 'Leave Absence Request')->first();

        // Assert that our database has the notification settings we expect
        $this->assertDatabaseHas('process_notification_settings', [
            'process_id' => $process->id,
            'notifiable_type' => 'requester',
            'notification_type' => 'started',
        ]);

        // Assert that our database currently has no notifications
        $this->assertDatabaseMissing('notifications', ['type' => 'ProcessMaker\Notifications\ProcessCreatedNotification']);

        // Trigger the process start event
        $url = route('api.process_events.trigger', [$process->id, 'event' => 'node_2']);
        $response = $this->apiCall('POST', $url);
        $response->assertStatus(201);

        // Assert that our database now has a notification
        $this->assertDatabaseHas('notifications', ['type' => 'ProcessMaker\Notifications\ProcessCreatedNotification']);
    }

    /**
     * Test that we do not receive notifications when they are disabled
     *
     * @return void
     */
    public function testRequestWithoutNotifications()
    {
        ScriptExecutor::setTestConfig('php');

        // Create a user
        $adminUser = factory(User::class)->create([
            'username' => 'admin',
            'is_administrator' => true,
        ]);

        // Seed the processes table.
        Artisan::call('db:seed', ['--class' => 'ProcessSeeder']);

        // Assert that our database has the process we need
        $this->assertDatabaseHas('processes', ['name' => 'Leave Absence Request']);

        // Get the process we'll be testing on
        $process = Process::where('name', 'Leave Absence Request')->first();

        // Assert that our database has the notification settings we expect
        $this->assertDatabaseHas('process_notification_settings', [
            'process_id' => $process->id,
            'notifiable_type' => 'requester',
            'notification_type' => 'started',
        ]);

        // Remove the notification settings
        ProcessNotificationSetting::where('process_id', $process->id)->delete();

        // Assert that our database no longer has the notification settings
        $this->assertDatabaseMissing('process_notification_settings', [
            'process_id' => $process->id,
        ]);

        // Assert that our database currently has no notifications
        $this->assertDatabaseMissing('notifications', ['type' => 'ProcessMaker\Notifications\ProcessCreatedNotification']);

        // Trigger the process start event
        $url = route('api.process_events.trigger', [$process->id, 'event' => 'node_2']);
        $response = $this->apiCall('POST', $url);
        $response->assertStatus(201);

        // Assert that our database still has no notifications
        $this->assertDatabaseMissing('notifications', ['type' => 'ProcessMaker\Notifications\ProcessCreatedNotification']);
    }

    /**
     * Verify that a notification is marked as read when the user edits the related task
     */
    public function testNotificationMarkedAsReadWhenTaskIsVisited()
    {
        // Create a request token to simulate that a new task is created for the user
        $token = factory(ProcessRequestToken::class)->create();

        //url to edit the task
        $taskUrl = route('tasks.edit', [$token], false);

        //Create a new notification for the task created above
        $response = $this->apiCall('POST', '/notifications', [
            'type' => 'TEST',
            'notifiable_type' => 'NOTIFIABLE/TEST',
            'data' => json_encode(['url' => $taskUrl]),
            'notifiable_id' => 1
        ]);
        $response->assertStatus(201);

        // Verify that there is one notification for the tasK:
        $beforeCount = Notification::where('data->url', $taskUrl)
                    ->whereNull('read_at')
                    ->get()
                    ->count();
        $this->assertEquals(1, $beforeCount);

        // Goto to the edit task screen and verify that the notification is read
        $response = $this->webCall('GET', $taskUrl);
        $afterCount = Notification::where('data->url', $taskUrl)
            ->whereNull('read_at')
            ->get()
            ->count();
        $this->assertEquals(0, $afterCount);
    }
}
