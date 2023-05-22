<?php

namespace Tests\Feature\Api;

use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\Notification;
use ProcessMaker\Models\User;
use Ramsey\Uuid\Uuid;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RequestHelper;

    const API_TEST_URL = '/notifications';

    const STRUCTURE = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
        'updated_at',
        'created_at',
    ];

    /**
     * Create new notification successfully
     */
    public function testCreateNotification()
    {
        //Post title duplicated
        $url = self::API_TEST_URL;
        $response = $this->apiCall('POST', $url, [
            'type' => 'TEST',
            'notifiable_type' => 'NOTIFIABLE/TEST',
            'data' => '[]',
            'notifiable_id' => 1,
        ]);

        //Validate the header status code
        $response->assertStatus(201);
    }

    /**
     * Get a list of Notifications without query parameters.
     */
    public function testListNotification()
    {
        $existing = Notification::count();

        Notification::factory()->count(10)->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user->id,
        ]);

        $response = $this->apiCall('GET', self::API_TEST_URL);

        //Validate the header status code
        $response->assertStatus(200);

        // Verify structure
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);

        // Verify count
        $this->assertEquals(10 + $existing, $response->json()['meta']['total']);
    }

    /**
     * Test to verify that the list dates are in the correct format (yyyy-mm-dd H:i+GMT)
     */
    public function testNotificationListDates()
    {
        $newEntity = Notification::factory()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user->id,
        ]);
        $route = self::API_TEST_URL;
        $response = $this->apiCall('GET', $route);

        $this->assertEquals(
            $newEntity->created_at->format('c'),
            $response->getData()->data[0]->created_at
        );

        $this->assertEquals(
            $newEntity->updated_at->format('c'),
            $response->getData()->data[0]->updated_at
        );
    }

    /**
     * Get a notification
     */
    public function testGetNotification()
    {
        //get the id from the factory
        $notification = Notification::factory()->create()->id;

        //load api
        $response = $this->apiCall('GET', self::API_TEST_URL . '/' . $notification);

        //Validate the status is correct
        $response->assertStatus(200);

        //verify structure
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Update notification in process
     */
    public function testUpdateNotification()
    {
        $url = self::API_TEST_URL . '/' . Notification::factory()->create()->id;

        //Load the starting notification data
        $verify = $this->apiCall('GET', $url);

        //Post saved success
        $response = $this->apiCall('PUT', $url, [
            'data' => '["test":1]',
        ]);

        //Validate the header status code
        $response->assertStatus(204);

        //Load the updated notification data
        $verify_new = $this->apiCall('GET', $url);

        //Check that it has changed
        $this->assertNotEquals($verify, $verify_new);
    }

    /**
     * Delete notification in process
     */
    public function testDeleteNotification()
    {
        //Remove notification
        $url = self::API_TEST_URL . '/' . Notification::factory()->create()->id;
        $response = $this->apiCall('DELETE', $url);

        //Validate the header status code
        $response->assertStatus(204);
    }

    /**
     * The notification does not exist in process
     */
    public function testDeleteNotificationNotExist()
    {
        //Notification not exist
        $url = self::API_TEST_URL . '/' . Notification::factory()->make()->id;
        $response = $this->apiCall('DELETE', $url);

        //Validate the header status code
        $response->assertStatus(405);
    }
}
