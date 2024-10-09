<?php

namespace Tests\Feature\Api;

use Faker\Factory as Faker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class EncryptedDataTest extends TestCase
{
    use RequestHelper;

    public function test_encrypt_text_ok()
    {
        // Initialize Faker
        $faker = Faker::create();

        // Prepare screen config
        $content = file_get_contents(__DIR__ . '/screens/test encrypted field.json');
        $content = str_replace('"9999999999"', $this->user->id, $content);
        $content = str_replace('"8888888888"', '', $content);
        $config = json_decode($content, true);

        // Create required dummy objects
        $screen = Screen::factory()->create(['config' => $config]);
        
        // Build data to send
        $data = [
            'field_name' => 'form_input_1',
            'plain_text' => $faker->sentence(),
            'screen_id' => $screen->id,
        ];

        // Call endpoint
        $response = $this->apiCall('POST', route('api.encrypted_data.encrypt_text'), $data);

        // Asserts
        $response->assertStatus(200);
        $this->assertTrue(Str::isUuid($response->content()));
    }

    public function test_encrypt_text_uuid()
    {
        // Initialize Faker
        $faker = Faker::create();

        // Prepare screen config
        $content = file_get_contents(__DIR__ . '/screens/test encrypted field.json');
        $content = str_replace('"9999999999"', $this->user->id, $content);
        $content = str_replace('"8888888888"', '', $content);
        $config = json_decode($content, true);

        // Create required dummy objects
        $screen = Screen::factory()->create(['config' => $config]);

        // Build data to send
        $data = [
            'field_name' => 'form_input_1',
            'plain_text' => $faker->sentence(),
            'screen_id' => $screen->id,
        ];

        // Call endpoint
        $response = $this->apiCall('POST', route('api.encrypted_data.encrypt_text'), $data);

        // Asserts
        $response->assertStatus(200);
        $uuid1 = $response->content();

        // The first time the record should be created
        $this->assertTrue(Str::isUuid($uuid1));

        // Build data to send
        $data = [
            'uuid' => $uuid1,
            'field_name' => 'form_input_1',
            'plain_text' => $faker->sentence(),
            'screen_id' => $screen->id,
        ];

        // Call endpoint
        $response = $this->apiCall('POST', route('api.encrypted_data.encrypt_text'), $data);

        // Asserts, the returned uuid should be the same that the previous beacuse only the data is updated
        $response->assertStatus(200);
        $uuid2 = $response->content();
        $this->assertEquals($uuid1, $uuid2);

        // Build data to send
        $data = [
            'uuid' => 'invalid_uuid',
            'field_name' => 'form_input_1',
            'plain_text' => $faker->sentence(),
            'screen_id' => $screen->id,
        ];

        // Call endpoint
        $response = $this->apiCall('POST', route('api.encrypted_data.encrypt_text'), $data);

        // Asserts, the returned status code should be 422 because is an invalid uuid
        $response->assertStatus(422);
    }

    public function test_encrypt_text_field_name_empty()
    {
        // Initialize Faker
        $faker = Faker::create();

        // Create required dummy objects
        $screen = Screen::factory()->create();
        
        // Build data to send
        $data = [
            'field_name' => '', // Empty
            'plain_text' => $faker->sentence(),
            'screen_id' => $screen->id,
        ];

        // Call endpoint
        $response = $this->apiCall('POST', route('api.encrypted_data.encrypt_text'), $data);

        // Asserts
        $response->assertStatus(422);
        $responseData = $response->json();
        $this->assertIsArray($responseData);
        $this->assertEquals($responseData['message'], 'The Field name field is required.');
        $this->assertIsArray($responseData['errors']);
    }

    public function test_encrypt_text_text_plain_empty()
    {
        // Initialize Faker
        $faker = Faker::create();

        // Create required dummy objects
        $screen = Screen::factory()->create();
        
        // Build data to send
        $data = [
            'field_name' => $faker->word(),
            'plain_text' => '', // Empty
            'screen_id' => $screen->id,
        ];

        // Call endpoint
        $response = $this->apiCall('POST', route('api.encrypted_data.encrypt_text'), $data);

        // Asserts
        $response->assertStatus(422);
        $responseData = $response->json();
        $this->assertIsArray($responseData);
        $this->assertEquals($responseData['message'], 'The Plain text field is required.');
        $this->assertIsArray($responseData['errors']);
    }

    public function test_encrypt_text_screen_id_not_found()
    {
        // Initialize Faker
        $faker = Faker::create();
        
        // Build data to send
        $data = [
            'field_name' => $faker->word(),
            'plain_text' => $faker->sentence(),
            'screen_id' => 9999999,
        ];

        // Call endpoint
        $response = $this->apiCall('POST', route('api.encrypted_data.encrypt_text'), $data);

        // Asserts
        $response->assertStatus(422);
        $responseData = $response->json();
        $this->assertIsArray($responseData);
        $this->assertEquals($responseData['message'], 'The selected Screen id is invalid.');
        $this->assertIsArray($responseData['errors']);
    }
}
