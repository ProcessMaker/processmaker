<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Database\Seeder;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessNotificationSetting;
use ProcessMaker\Models\Permission;
use ProcessMaker\Facades\WorkflowManager;
use Illuminate\Support\Facades\Artisan;
use Tests\Feature\Shared\RequestHelper;
use Illuminate\Http\UploadedFile;

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
}
