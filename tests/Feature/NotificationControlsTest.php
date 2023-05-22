<?php

namespace Tests\Feature;

use Database\Seeders\ProcessSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Request;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Notification;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessNotificationSetting;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\ProcessCanceledNotification;
use ProcessMaker\Notifications\ProcessCreatedNotification;
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
        // Create a user
        $adminUser = User::factory()->create([
            'username' => 'admin',
            'is_administrator' => true,
        ]);

        // Seed the processes table.
        Artisan::call('db:seed', ['--class' => 'ProcessSeeder']);

        // Assert that our database has the process we need
        $this->assertDatabaseHas('processes', ['name' => 'Leave Absence Request']);

        // Get the process we'll be testing on
        $process = Process::where('name', 'Leave Absence Request')->first();

        // Assign a process manager
        $process->manager_id = $adminUser->getKey();
        $process->save();

        // Allow the process manager to receive canceled notificaitons
        ProcessNotificationSetting::factory()->create([
            'process_id' => $process->getKey(),
            'notifiable_type' => 'manager',
            'notification_type' => 'canceled',
        ]);

        // Assert that our database has the notification settings we expect
        $this->assertDatabaseHas('process_notification_settings', [
            'process_id' => $process->id,
            'notifiable_type' => 'requester',
            'notification_type' => 'started',
        ]);
        $this->assertDatabaseHas('process_notification_settings', [
            'process_id' => $process->id,
            'notifiable_type' => 'manager',
            'notification_type' => 'canceled',
        ]);

        // Assert that our database currently has no notifications
        $this->assertDatabaseMissing('notifications', ['type' => 'ProcessMaker\Notifications\ProcessCreatedNotification']);

        // Trigger the process start event
        $url = route('api.process_events.trigger', [$process->id, 'event' => 'node_2']);
        $response = $this->apiCall('POST', $url);
        $response->assertStatus(201);

        // Obtain request ID
        $responseId = $response->getData()->id;

        // Assert that our database now has a process created notification
        $this->assertDatabaseHas('notifications', [
            'type' => ProcessCreatedNotification::class,
        ]);

        // Cancel the process
        $url = route('api.requests.update', [$responseId]);
        $response = $this->apiCall('PUT', $url, ['status' => 'CANCELED']);
        $response->assertStatus(204);

        // Assert that our database now has a process canceled notification
        $this->assertDatabaseHas('notifications', [
            'type' => ProcessCanceledNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $adminUser->getKey(),
        ]);
    }

    /**
     * Test that we do not receive notifications when they are disabled
     *
     * @return void
     */
    public function testRequestWithoutNotifications()
    {
        // Create a user
        $adminUser = User::factory()->create([
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
        $token = ProcessRequestToken::factory()->create();

        //url to edit the task
        $taskUrl = route('tasks.edit', [$token], false);

        //Create a new notification for the task created above
        $response = $this->apiCall('POST', '/notifications', [
            'type' => 'TEST',
            'notifiable_type' => 'NOTIFIABLE/TEST',
            'data' => json_encode(['url' => $taskUrl]),
            'notifiable_id' => 1,
            'url' => $taskUrl,
        ]);
        $response->assertStatus(201);

        // Verify that there is one notification for the tasK:
        $beforeCount = Notification::where('url', $taskUrl)
                    ->whereNull('read_at')
                    ->get()
                    ->count();
        $this->assertEquals(1, $beforeCount);

        // Goto to the edit task screen and verify that the notification is read
        $response = $this->webCall('GET', $taskUrl);
        $afterCount = Notification::where('url', $taskUrl)
            ->whereNull('read_at')
            ->get()
            ->count();
        $this->assertEquals(0, $afterCount);
    }
}
