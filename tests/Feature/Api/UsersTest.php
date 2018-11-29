<?php

namespace Tests\Feature\Api;

use Carbon\Carbon;
use Faker\Factory as Faker;
use ProcessMaker\Models\User;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use Illuminate\Support\Facades\Hash;

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
        'created_at'
    ];

    /**
     * Test verify the parameter required for create form
     */
    public function testNotCreatedForParameterRequired()
    {
        //Post should have the parameter required
        $response = $this->apiCall('POST', self::API_TEST_URL, []);

        //Validate the header status code
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Create new user successfully
     */
    public function testCreateUser()
    {
        //Post title duplicated
        $faker = Faker::create();
        $url = self::API_TEST_URL;
        $response = $this->apiCall('POST', $url, [
            'username' => 'newuser',
            'email' => $faker->email,
            'password' => $faker->sentence(10)
        ]);

        //Validate the header status code
        $response->assertStatus(201);
    }

    /**
     * Can not create a user with an existing username
     */
    public function testNotCreateUserWithUsernameExists()
    {
        factory(User::class)->create([
            'username' => 'mytestusername',
        ]);

        //Post username duplicated
        $faker = Faker::create();
        $response = $this->apiCall('POST', self::API_TEST_URL, [
            'username' => 'mytestusername',
            'email' => $faker->email,
            'deuserion' => $faker->sentence(10)
        ]);

        //Validate the header status code
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Get a list of Users without query parameters.
     */
    public function testListUser()
    {

        User::query()->delete();

        $faker = Faker::create();

        factory(User::class, 10)->create();

        $response = $this->apiCall('GET', self::API_TEST_URL);

        //Validate the header status code
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
        $newEntity = factory(User::class)->create(['username' => $username]);
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

        factory(User::class)->create([
            'username' => $username,
        ]);

        //List User with filter option
        $perPage = Faker::create()->randomDigitNotNull;
        $query = '?page=1&per_page=' . $perPage . '&order_by=firstname&order_direction=DESC&filter=' . $username;
        $response = $this->apiCall('GET', self::API_TEST_URL . $query);

        //Validate the header status code
        $response->assertStatus(200);

        //verify structure paginate
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
     * Get a user
     */
    public function testGetUser()
    {
        //get the id from the factory
        $user = factory(User::class)->create()->id;

        //load api
        $response = $this->apiCall('GET', self::API_TEST_URL . '/' . $user);

        //Validate the status is correct
        $response->assertStatus(200);

        //verify structure
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Get a user with the memberships
     */
    // public function testGetUserIncledMembership()
    // {
    //     //get the id from the factory
    //     $user = factory(User::class)->create()->id;
    //
    //     //load api
    //     $response = $this->apiCall('GET', self::API_TEST_URL. '/' . $user . '?include=memberships');
    //
    //     //Validate the status is correct
    //     $response->assertStatus(200);
    //
    //     //verify structure
    //     $response->assertJsonFragment(['memberships']);
    // }

    /**
     * Parameters required for update of user
     */
    public function testUpdateUserParametersRequired()
    {
        //The post must have the required parameters
        $url = self::API_TEST_URL . '/' . factory(User::class)->create()->id;

        $response = $this->apiCall('PUT', $url, [
            'username' => ''
        ]);

        //Validate the header status code
        $response->assertStatus(422);
    }

    /**
     * Update user in process
     */
    public function testUpdateUser()
    {
        $faker = Faker::create();

        $url = self::API_TEST_URL . '/' . factory(User::class)->create()->id;

        //Load the starting user data
        $verify = $this->apiCall('GET', $url);

        //Post saved success
        $response = $this->apiCall('PUT', $url, [
            'username' => 'updatemytestusername',
            'email' => $faker->email,
            'firstname' => $faker->firstName,
            'lastname' => $faker->lastName,
            'phone' => $faker->phoneNumber,
            'cell' => $faker->phoneNumber,
            'fax' => $faker->phoneNumber,
            'address' => $faker->streetAddress,
            'city' => $faker->city,
            'state' => $faker->stateAbbr,
            'postal' => $faker->postcode,
            'country' => $faker->country,
            'timezone' => $faker->timezone,
            'birthdate' => $faker->dateTimeThisCentury->format('Y-m-d'),
            'password' => $faker->password(6, 6),
        ]);

        //Validate the header status code
        $response->assertStatus(204);

        //Load the updated user data
        $verify_new = $this->apiCall('GET', $url);

        //Check that it has changed
        $this->assertNotEquals($verify, $verify_new);

    }

    /**
     * Check that the validation wont allow duplicate usernames
     */
    public function testUpdateUserTitleExists()
    {
        $user1 = factory(User::class)->create([
            'username' => 'MyUserName',
        ]);

        $user2 = factory(User::class)->create();

        $url = self::API_TEST_URL . '/' . $user2->id;

        $response = $this->apiCall('PUT', $url, [
            'username' => 'MyUserName',
        ]);
        //Validate the header status code
        $response->assertStatus(422);
        $response->assertSeeText('The username has already been taken');
    }

    /**
     * Delete user in process
     */
    public function testDeleteUser()
    {
        //Remove user
        $url = self::API_TEST_URL . '/' . factory(User::class)->create()->id;
        $response = $this->apiCall('DELETE', $url);

        //Validate the header status code
        $response->assertStatus(204);
    }

    /**
     * The user does not exist in process
     */
    public function testDeleteUserNotExist()
    {
        //User not exist
        $url = self::API_TEST_URL . '/' . factory(User::class)->make()->id;
        $response = $this->apiCall('DELETE', $url);

        //Validate the header status code
        $response->assertStatus(405);
    }

}
