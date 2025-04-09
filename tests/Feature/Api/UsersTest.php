<?php

namespace Tests\Feature\Api;

use Database\Seeders\PermissionSeeder;
use Faker\Factory as Faker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Redis;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\RecommendationUser;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use RequestHelper;

    const API_TEST_URL = '/users';

    const STRUCTURE = [
        'id',
        'username',
        'email',
        // 'password',
        'firstname',
        'lastname',
        'status',
        'address',
        'city',
        'state',
        'postal',
        'country',
        'phone',
        'fax',
        'cell',
        'title',
        'birthdate',
        'timezone',
        'language',
        'expires_at',
        'updated_at',
        'created_at',
    ];

    public function getUpdatedData()
    {
        $faker = Faker::create();

        return [
            'username' => 'newusername',
            'email' => $faker->email(),
            'firstname' => $faker->firstName(),
            'lastname' => $faker->lastName(),
            'phone' => $faker->phoneNumber(),
            'cell' => $faker->phoneNumber(),
            'fax' => $faker->phoneNumber(),
            'address' => $faker->streetAddress(),
            'city' => $faker->city(),
            'state' => $faker->stateAbbr(),
            'postal' => $faker->postcode(),
            'country' => $faker->country(),
            'timezone' => $faker->timezone(),
            'status' => $faker->randomElement(['ACTIVE', 'INACTIVE']),
            'birthdate' => $faker->dateTimeThisCentury()->format('Y-m-d'),
            'password' => $this->makePassword(),
        ];
    }

    protected function withUserSetup()
    {
        $this->user->is_administrator = true;
        $this->user->save();

        (new PermissionSeeder)->run($this->user);
    }

    /**
     * Test verify the parameter required for create form
     */
    public function testNotCreatedForParameterRequired()
    {
        // Post should have the parameter required
        $response = $this->apiCall('POST', self::API_TEST_URL, []);

        // Validate the header status code
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Create new user successfully
     */
    public function testCreateUser()
    {
        // Post title duplicated
        $faker = Faker::create();
        $url = self::API_TEST_URL;
        $response = $this->apiCall('POST', $url, [
            'username' => 'newuser',
            'firstname' => 'name',
            'lastname' => 'name',
            'email' => $faker->email(),
            'status' => $faker->randomElement(['ACTIVE', 'INACTIVE']),
            'password' => $this->makePassword(),
        ]);

        // Validate the header status code
        $response->assertStatus(201);
    }

    /**
     * Create new user and the email task notification needs to enable per default
     */
    public function testFlagEmailTaskNotification()
    {
        $faker = Faker::create();
        $url = self::API_TEST_URL;
        $response = $this->apiCall('POST', $url, [
            'username' => 'user_test_' . $faker->randomDigit(),
            'firstname' => 'name',
            'lastname' => 'name',
            'email' => $faker->email(),
            'status' => 'ACTIVE',
            'password' => $this->makePassword(),
        ]);

        // Test default value email_task_notification was enable
        $response->assertStatus(201);
        $newUser = $response->json();
        $this->assertEquals(1, $newUser['email_task_notification']);
        // Test updating email_task_notification is disable
        $userId = $newUser['id'];
        $payload = [
            'username' => $newUser['username'],
            'firstname' => $newUser['firstname'],
            'lastname' => $newUser['lastname'],
            'email' => $newUser['email'],
            'status' => $newUser['status'],
            'email_task_notification' => 0,
        ];
        $response = $this->apiCall('PUT', route('api.users.update', $userId), $payload);
        $response->assertStatus(204);
        // Validate flag email_task_notification was disable
        $this->assertDatabaseHas('users', [
            'email_task_notification' => 0,
        ]);

        // Test updating email_task_notification is enable
        $payload['email_task_notification'] = 1;
        $response = $this->apiCall('PUT', route('api.users.update', $userId), $payload);
        $response->assertStatus(204);
        // Validate flag email_task_notification was enable
        $this->assertDatabaseHas('users', [
            'email_task_notification' => 0,
        ]);
    }

    public function testCreatePreviouslyDeletedUser()
    {
        $url = self::API_TEST_URL;

        $deletedUser = (object) [];
        User::withoutEvents(function () use (&$deletedUser) {
            $deletedUser = User::factory()->create([
                'deleted_at' => '2019-01-01',
                'status' => 'ACTIVE',
            ]);
        });

        $params = [
            'username' => $deletedUser->username,
            'firstname' => 'foo',
            'lastname' => 'bar',
            'email' => $deletedUser->email,
            'status' => 'ACTIVE',
            'password' => $this->makePassword(),
        ];

        $response = $this->apiCall('POST', $url, $params);

        $this->assertArrayHasKey('errors', $response->json());

        $this->assertArrayHasKey('username', $response->json()['errors']);

        $this->assertEquals('The Username has already been taken.', $response->json()['errors']['username'][0]);
    }

    public function testDefaultValuesOfUser()
    {
        config()->set('app.timezone', 'America/Los_Angeles');
        putenv('DATE_FORMAT=m/d/Y H:i');
        putenv('APP_LANG=en');
        $faker = Faker::create();
        $url = self::API_TEST_URL;

        // Create a user without setting fields that have default.
        $response = $this->apiCall('POST', $url, [
            'username' => 'username1',
            'firstname' => $faker->firstName(),
            'lastname' => $faker->lastName(),
            'email' => $faker->email(),
            'status' => $faker->randomElement(['ACTIVE', 'INACTIVE']),
            'password' => $faker->password(8) . 'A' . '1.',
        ]);

        // Validate that the created user has the correct default values.
        $response->assertStatus(201);
        $createdUser = $response->json();
        // Verify that a user created from Rest API has the timezone defined in settings > users > Timezone when the request does not have the timezone parameter.
        $this->assertEquals(config()->get('app.timezone'), $createdUser['timezone']);
        $this->assertEquals(getenv('DATE_FORMAT'), $createdUser['datetime_format']);
        $this->assertEquals(getenv('APP_LANG'), $createdUser['language']);

        // Create a user setting fields that have default.
        $setting = Setting::factory()->create([
            'key' => 'users.timezone',
            'format' => 'object',
            'config' => ['timezone' => 'America/New_York'],
        ]);
        $dateFormat = 'testFormat';
        $response = $this->apiCall('POST', $url, [
            'username' => 'username2',
            'firstname' => $faker->firstName(),
            'lastname' => $faker->lastName(),
            'email' => $faker->email(),
            'status' => $faker->randomElement(['ACTIVE', 'INACTIVE']),
            'password' => $this->makePassword(),
            'datetime_format' => $dateFormat,
        ]);

        // Validate that the created user has the correct values.
        $response->assertStatus(201);
        $createdUser = $response->json();
        // Verify that a user created from UI has the timezone defined in settings > users > Timezone.
        $this->assertEquals($createdUser['timezone'], $setting->config->timezone);
        $this->assertEquals($createdUser['datetime_format'], $dateFormat);

        // Create a new user and define a timezone on the request.
        $response = $this->apiCall('POST', $url, [
            'username' => 'username3',
            'firstname' => $faker->firstName(),
            'lastname' => $faker->lastName(),
            'email' => $faker->email(),
            'status' => $faker->randomElement(['ACTIVE', 'INACTIVE']),
            'password' => $this->makePassword(),
            'timezone' => 'America/Monterrey',
        ]);

        // Verify that a user created from Rest API has the timezone defined when the request have a timezone different to default/settings timezone value.
        $response->assertStatus(201);
        $createdUser = $response->json();
        $this->assertEquals('America/Monterrey', $createdUser['timezone']);
    }

    /**
     * Can not create a user with an existing username
     */
    public function testNotCreateUserWithUsernameExists()
    {
        User::factory()->create([
            'username' => 'mytestusername',
        ]);

        // Post username duplicated
        $faker = Faker::create();
        $response = $this->apiCall('POST', self::API_TEST_URL, [
            'username' => 'mytestusername',
            'email' => $faker->email(),
            'deuserion' => $faker->sentence(10),
        ]);

        // Validate the header status code
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Get a list of Users without query parameters.
     */
    public function testListUser()
    {
        User::query()->delete();

        User::factory()->count(10)->create();

        $response = $this->apiCall('GET', self::API_TEST_URL);

        // Validate the header status code
        $response->assertStatus(200);

        // Verify structure
        $response->assertJsonStructure([
            'data' => ['*' => self::STRUCTURE],
            'meta',
        ]);

        // Verify count
        $this->assertEquals(10, $response->json()['meta']['total']);
    }

    /**
     * Test to verify that the list dates are in the correct format (yyyy-mm-dd H:i+GMT)
     */
    public function testUserListDates()
    {
        $username = 'userTestTimezone';
        $newEntity = User::factory()->create(['username' => $username]);
        $route = self::API_TEST_URL . '?filter=' . $username;

        $response = $this->apiCall('GET', $route);

        $this->assertEquals(
            $newEntity->updated_at->format('c'),
            $response->getData()->data[0]->updated_at
        );

        $this->assertEquals(
            $newEntity->created_at->format('c'),
            $response->getData()->data[0]->created_at
        );
    }

    /**
     * Get a list of User with parameters
     */
    public function testListUserWithQueryParameter()
    {
        $username = 'mytestusername';

        User::factory()->create([
            'username' => $username,
        ]);

        // List User with filter option
        $perPage = Faker::create()->randomDigitNotNull;
        $query = '?page=1&per_page=' . $perPage . '&order_by=firstname&order_direction=DESC&filter=' . $username;
        $response = $this->apiCall('GET', self::API_TEST_URL . $query);

        // Validate the header status code
        $response->assertStatus(200);

        // verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);

        // Verify return data
        $this->assertEquals(1, $response->json()['meta']['total']);
        $this->assertEquals('firstname', $response->json()['meta']['sort_by']);
        $this->assertEquals('DESC', $response->json()['meta']['sort_order']);
    }

    /**
     * Tests filtering a user based off of email address
     */
    public function testFetchUserByEmailAddressFilter()
    {
        User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $query = '?filter=' . urlencode('test@example.com');
        $response = $this->apiCall('GET', self::API_TEST_URL . $query);

        // Validate the header status code
        $response->assertStatus(200);

        // verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);

        // Verify return data
        $this->assertEquals(1, $response->json()['meta']['total']);
        $this->assertEquals('test@example.com', $response->json()['data'][0]['email']);
    }

    /**
     * Get a user
     */
    public function testGetUser()
    {
        // get the id from the factory
        $user = User::factory()->create()->id;

        // load api
        $response = $this->apiCall('GET', self::API_TEST_URL . '/' . $user);

        // Validate the status is correct
        $response->assertStatus(200);

        // verify structure
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Parameters required for update of user
     */
    public function testUpdateUserParametersRequired()
    {
        // The post must have the required parameters
        $url = self::API_TEST_URL . '/' . User::factory()->create()->id;

        $response = $this->apiCall('PUT', $url, [
            'username' => '',
        ]);

        // Validate the header status code
        $response->assertStatus(422);
    }

    /**
     * Update user in process
     */
    public function testUpdateUser()
    {
        $faker = Faker::create();

        $url = self::API_TEST_URL . '/' . User::factory()->create()->id;

        // Load the starting user data
        $verify = $this->apiCall('GET', $url);

        // Send the same email to avoid the email validation
        $updateData = $this->getUpdatedData();
        $updateData['email'] = $verify['email'];

        // Post saved success
        $response = $this->apiCall('PUT', $url, $updateData);

        // Validate the header status code
        $response->assertStatus(204);

        // Load the updated user data
        $verify_new = $this->apiCall('GET', $url);

        // Check that it has changed
        $this->assertNotEquals($verify, $verify_new);
    }

    /**
     * Update user in process
     */
    public function testUpdateUserForceChangePasswordFlag()
    {
        $faker = Faker::create();

        $user = User::factory()->create();

        $url = self::API_TEST_URL . '/' . $user->id;

        // Post saved success
        $response = $this->apiCall('PUT', $url, [
            'username' => 'updatemytestusername',
            'email' => $user->email,
            'firstname' => $faker->firstName(),
            'lastname' => $faker->lastName(),
            'status' => $faker->randomElement(['ACTIVE', 'INACTIVE']),
            'password' => $this->makePassword(),
            'force_change_password' => 0,
        ]);

        // Validate the header status code
        $response->assertStatus(204);

        // Validate Flag force_change_password was changed
        $this->assertDatabaseHas('users', [
            'force_change_password' => 0,
        ]);
    }

    /**
     * Check that the validation wont allow duplicate usernames
     */
    public function testUpdateUserTitleExists()
    {
        $user1 = User::factory()->create([
            'username' => 'MyUserName',
        ]);

        $user2 = User::factory()->create();

        $url = self::API_TEST_URL . '/' . $user2->id;

        $response = $this->apiCall('PUT', $url, [
            'username' => 'MyUserName',
        ]);
        // Validate the header status code
        $response->assertStatus(422);
        $response->assertSeeText('The Username has already been taken');
    }

    /**
     * Delete user in process
     */
    public function testDeleteUser()
    {
        // Remove user
        $url = self::API_TEST_URL . '/' . User::factory()->create()->id;
        $response = $this->apiCall('DELETE', $url);

        // Validate the header status code
        $response->assertStatus(204);
    }

    /**
     * The user does not exist in process
     */
    public function testDeleteUserNotExist()
    {
        // User not exist
        $url = self::API_TEST_URL . '/' . User::factory()->make()->id;
        $response = $this->apiCall('DELETE', $url);

        // Validate the header status code
        $response->assertStatus(405);
    }

    /**
     * The user can upload an avatar
     */
    public function testUpdateUserAvatar()
    {
        // Create a new user
        $user = User::factory()->create([
            'username' => 'AvatarUser',
        ]);

        // Set our API url for this users
        $url = self::API_TEST_URL . '/' . $user->id;

        // Create a fake image and encode it to base64
        $fakeImage = UploadedFile::fake()
                                 ->image('avatar.jpg', 1200, 1200)
                                 ->size(1500)
                                 ->get();
        $avatar = 'data:image/png;base64,' . base64_encode($fakeImage);

        // Update the user with the fake image as an avatar
        $putResponse = $this->apiCall('PUT', $url, [
            'username' => $user->username,
            'firstname' => 'name',
            'lastname' => 'name',
            'email' => $user->email,
            'status' => 'ACTIVE',
            'avatar' => $avatar,
        ]);

        // Validate the header status code
        $putResponse->assertStatus(204);

        // Request the user from the API
        $getResponse = $this->apiCall('GET', $url);

        // Assert that the 'avatar' key exists
        $getResponse->assertJsonStructure(['avatar']);

        // Assert that the file was saved
        $json = $getResponse->json();
        $path = parse_url($json['avatar'], PHP_URL_PATH);
        $media = $user->getMedia('profile')[0];
        $this->assertEquals("/storage/profile/{$media->id}/img.png", $path);
        $this->assertFileExists($media->getPath());
    }

    /**
     * Tests the archiving and restoration of a process
     * @group agustin
     */
    public function testRestoreSoftDeletedUser()
    {
        // create an user
        $user = User::factory()->create([
            'email' => 'test@email.com',
            'username' => 'mytestusername',
        ]);
        $id = $user->id;

        // Assert that the user is listed
        $response = $this->apiCall('GET', self::API_TEST_URL);
        $response->assertJsonFragment(['id' => $id]);

        // Soft delete the user
        $response = $this->apiCall('DELETE', self::API_TEST_URL . '/' . $id);
        $response->assertStatus(204);

        // Assert that the user is not listed on the main index
        $response = $this->apiCall('GET', self::API_TEST_URL);
        $response->assertJsonMissing(['id' => $id]);

        // Restore the user by email
        $response = $this->apiCall('PUT', self::API_TEST_URL . '/restore', [
            'email' => $user->email,
        ]);
        $response->assertStatus(200);

        // Assert that the user is listed
        $response = $this->apiCall('GET', self::API_TEST_URL);
        $response->assertJsonFragment(['id' => $id]);

        // Soft delete the user
        $response = $this->apiCall('DELETE', self::API_TEST_URL . '/' . $id);
        $response->assertStatus(204);

        // Assert that the user is not listed on the main index
        $response = $this->apiCall('GET', self::API_TEST_URL);
        $response->assertJsonMissing(['id' => $id]);

        // Restore the user by username
        $response = $this->apiCall('PUT', self::API_TEST_URL . '/restore', [
            'username' => $user->username,
        ]);
        $response->assertStatus(200);

        // Assert that the user is listed
        $response = $this->apiCall('GET', self::API_TEST_URL);
        $response->assertJsonFragment(['id' => $id]);

        // Soft delete the user
        $response = $this->apiCall('DELETE', self::API_TEST_URL . '/' . $id);
        $response->assertStatus(204);

        // Assert that the user is not listed on the main index
        $response = $this->apiCall('GET', self::API_TEST_URL);
        $response->assertJsonMissing(['id' => $id]);

        // Restore the user by username and different email
        $response = $this->apiCall('PUT', self::API_TEST_URL . '/restore', [
            'email' => 'changed' . $user->email,
            'username' => $user->username,
        ]);
        $response->assertStatus(200);

        // Assert that the user is listed
        $response = $this->apiCall('GET', self::API_TEST_URL);
        $response->assertJsonFragment(['id' => $id]);
    }

    public function testCreateWithoutPassword()
    {
        $payload = [
            'firstname' => 'foo',
            'lastname' => 'bar',
            'email' => 'foobar@test.com',
            'username' => 'foobar',
            'status' => 'ACTIVE',
        ];
        $response = $this->apiCall('POST', self::API_TEST_URL, $payload);
        $response->assertStatus(422);
        $json = $response->json();
        $this->assertEquals('The Password field is required.', $json['errors']['password'][0]);

        $payload['password'] = 'abc';
        $response = $this->apiCall('POST', self::API_TEST_URL, $payload);
        $response->assertStatus(422);
        $json = $response->json();
        $this->assertTrue(in_array('The Password field must be at least 8 characters.', $json['errors']['password']));

        $payload['password'] = 'Abc12345_';
        $response = $this->apiCall('POST', self::API_TEST_URL, $payload);
        $response->assertStatus(201);
        $json = $response->json();
        $userId = $json['id'];

        // Test updating the users's password

        $payload['password'] = 'abc';
        $response = $this->apiCall('PUT', route('api.users.update', $userId), $payload);
        $response->assertStatus(422);
        $json = $response->json();
        $this->assertTrue(in_array('The Password field must be at least 8 characters.', $json['errors']['password']));

        $payload['password'] = 'Abc12345_';
        $response = $this->apiCall('PUT', route('api.users.update', $userId), $payload);
        $response->assertStatus(204);

        // It's OK to update a user without the password
        unset($payload['password']);
        $response = $this->apiCall('PUT', route('api.users.update', $userId), $payload);
        $response->assertStatus(204);
    }

    /**
     * Create and validate username
     */
    public function testCreateUserValidateUsername()
    {
        // Valid cases
        $usernames = [
            'admin',
            'john.doe',
            'heaney-esperanza',
            'jackeline53@rowe.com',
            'antonette06@yahoo.com',
            'metz.tierra@quigley.com',
            'roberts-kaitlin@gmail.com',
            'elise~reichert+1@gmail.com',
            'oleta#runolfsdottir@mertz.net',
            'simple@example.com',
            'very.common@example.com',
            'disposable.style.email.with+symbol@example.com',
            'other.email-with-hyphen@example.com',
            'fully-qualified-domain@example.com',
            // may go to user.name@example.com inbox depending on mail server
            'user.name+tag+sorting@example.com',
            // (one-letter local-part)
            'x@example.com',
            'example-indeed@strange-example.com',
            'example@s.example',
            // (space between the quotes)
            // (bangified host route used for uucp mailers)
            'mailhost!username@example.org',
            // (local part ending with non-alphanumeric character from the list of allowed printable characters)
            'user-@example.org',
            '123',
            'abc',
        ];

        $faker = Faker::create();
        $url = self::API_TEST_URL;
        foreach ($usernames as $username) {
            $response = $this->apiCall('POST', $url, $data = [
                'username' => $username,
                'firstname' => $faker->firstName(),
                'lastname' => $faker->lastName(),
                'email' => $faker->email(),
                'status' => $faker->randomElement(['ACTIVE', 'INACTIVE']),
                'password' => $this->makePassword(),
            ]);
            // Validate the header status code
            $response->assertStatus(201);
        }

        // Invalid cases
        $usernames = [
            '12',
            'ab',
            'test/test@test.com',
            // (space between the quotes)
            '" "@example.org',
            // (quoted double dot)
            '"john..doe"@example.org',
            // (bangified host route used for uucp mailers)
            'mailhost!username@example.org',
            // (% escaped mail route to user@example.com via example.org)
            'user%example.com@example.org',
        ];

        $faker = Faker::create();
        $url = self::API_TEST_URL;
        foreach ($usernames as $username) {
            $response = $this->apiCall('POST', $url, $data = [
                'username' => $username,
                'firstname' => $faker->firstName(),
                'lastname' => $faker->lastName(),
                'email' => $faker->email(),
                'status' => $faker->randomElement(['ACTIVE', 'INACTIVE']),
                'password' => $faker->sentence(10),
            ]);
            // Validate the header status code
            $response->assertStatus(422);
        }
    }

    /**
     * Update username and password
     * If is an admin user can edit username and password himself
     */
    public function testUpdateUserAdmin()
    {
        $url = self::API_TEST_URL . '/' . $this->user->id;

        // Load the starting user data
        $verify = $this->apiCall('GET', $url);

        $updateData = $this->getUpdatedData();
        $updateData['email'] = $verify['email'];

        // Post saved success
        $response = $this->apiCall('PUT', $url, $updateData);

        // Validate the header status code
        $response->assertStatus(204);

        // Load the updated user data
        $verifyNew = $this->apiCall('GET', $url);

        // Check that it has changed
        $this->assertNotEquals($verify, $verifyNew);
    }

    /**
     * Update username and password
     * If is a user without permission can not edit and a user with permission can edit himself
     */
    public function testUpdateUserNotAdmin()
    {
        // Without permission
        $this->user = User::factory()->create(['status' => 'ACTIVE']);
        $this->user->is_administrator = false;
        $this->user->save();
        $this->user->refresh();
        $this->flushSession();

        $url = self::API_TEST_URL . '/' . $this->user->id;

        // Load the starting user data
        $verify = $this->apiCall('GET', $url);

        $updateData = $this->getUpdatedData();
        $updateData['email'] = $verify['email'];

        $response = $this->apiCall('PUT', $url, $updateData);

        // Validate the header status code
        $response->assertStatus(403);

        //  With permission
        $this->user->giveDirectPermission('edit-user-and-password');
        $this->user->save();
        $this->user->refresh();
        $this->flushSession();

        $updateData = $this->getUpdatedData();
        $updateData['email'] = $verify['email'];

        // Post saved success
        $response = $this->apiCall('PUT', $url, $updateData);

        // Validate the header status code
        $response->assertStatus(204);

        // Load the updated user data
        $verifyNew = $this->apiCall('GET', $url);

        // Check that it has changed
        $this->assertNotEquals($verify, $verifyNew);
    }

    public function testDisableRecommendations()
    {
        RecommendationUser::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(1, RecommendationUser::where('user_id', $this->user->id)->count());

        $url = self::API_TEST_URL . '/' . $this->user->id;

        $updateData = $this->getUpdatedData();
        $updateData['email'] = $this->user->email;

        $data = [
            ...$updateData,
            'meta' => [
                'disableRecommendations' => true,
            ],
        ];
        $this->apiCall('PUT', $url, $data);

        $this->assertEquals(0, RecommendationUser::where('user_id', $this->user->id)->count());
    }

    public function testGetUsersTaskCount()
    {
        $admin = $this->user;
        $user = User::factory()->create();
        $groupUser = User::factory()->create();
        $group = Group::factory()->create();
        GroupMember::factory()->create([
            'group_id' => $group->id,
            'member_id' => $groupUser->id,
            'member_type' => User::class,
        ]);

        $process = Process::factory()->create([
            'user_id' => $admin->id,
        ]);

        $bpmn = file_get_contents(__DIR__ . '/../../Fixtures/task_with_user_group_assignment.bpmn');
        $bpmn = str_replace([
            '[assigned-users]',
            '[assigned-groups]',
        ], [
            $user->id,
            $group->id,
        ], $bpmn);

        $process->bpmn = $bpmn; // Save separately from factory::create to utilize ProcessTaskAssignmentsTrait
        $process->save();

        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'user_id' => $admin->id,
        ]);

        $tasks = ProcessRequestToken::factory(3)->create([
            'process_id' => $process->id,
            'process_request_id' => $request->id,
            'element_id' => 'node_2',
            'user_id' => $user->id,
            'status' => 'ACTIVE',
        ]);

        $result = $this->apiCall('GET', route('api.users.users_task_count', [
            'assignable_for_task_id' => $tasks[0]->id,
        ]));

        $users = $result->json()['data'];

        // Assert only the $user and $groupUser are in the list
        $this->assertContains($user->id, array_column($users, 'id'));
        $this->assertContains($groupUser->id, array_column($users, 'id'));

        // Assert the $user has 3 active tasks
        $tokenCount = collect($users)->first(fn ($r) => $r['id'] === $user->id)['active_tasks_count'];
        $this->assertEquals(3, $tokenCount);

        // Make a request without specifying assignable_for_task_id
        $result = $this->apiCall('GET', route('api.users.users_task_count'));
        $users = $result->json()['data'];

        // Assert the list of users now contains the admin user
        $this->assertContains($admin->id, collect($users)->pluck('id')->toArray());
    }

    /**
     * Test save and get filters per user saved in cache
     */
    public function testGetDefaultUserConfiguration()
    {
        // Define an example of filters to save
        $values = [
            'filters' => [
                [
                    'subject' => [
                        'type' => 'Field',
                        'value' => 'case_number',
                    ],
                    'operator' => '=',
                    'value' => '885',
                ],
                [
                    'subject' => [
                        'type' => 'Field',
                        'value' => 'case_title',
                    ],
                    'operator' => '=',
                    'value' => 'TCP4_Case_title',
                ],
            ],
            'order' => [
                'by' => 'id',
                'dir' => 'ASC',
            ],
        ];
        // Define the page filter to save
        $pagesSaveFilters = [
            'casesFilter',
            'casesFilter|in_progress',
            'casesFilter|completed',
            'casesFilter|all',
        ];
        $randomKey = array_rand($pagesSaveFilters);
        $name = $pagesSaveFilters[$randomKey];
        // Call the api PUT
        $response = $this->apiCall('PUT', '/users/store_filter_configuration/' . $name, $values);
        // Validate the header status code
        $response->assertStatus(200);
        $this->assertNotEmpty($response);

        // Call the api GET
        $response = $this->apiCall('GET', '/users/get_filter_configuration/' . $name);
        // Validate the header status code
        $response->assertStatus(200);
        $this->assertNotEmpty($response);
        $response->assertJson(['data' => $values]);
    }

    private function makePassword()
    {
        $faker = Faker::create();

        return substr($faker->password(8), 0, 20) . 'A_' . '1';
    }
}
