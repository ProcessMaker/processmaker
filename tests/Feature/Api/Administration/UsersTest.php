<?php

namespace Tests\Feature\Api\Administration;

use Auth;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use ProcessMaker\Transformers\UserTransformer;
use Tests\Feature\Api\ApiTestCase;

/**
 * Tests the Users API Endpoints with expected values
 */
class UsersTest extends ApiTestCase
{

    use DatabaseTransactions;

    const API_TEST_USERS = '/api/1.0/users';
    const API_TEST_PROFILE = '/api/1.0/admin/';

    private function doLogin($username, $password){
          return $this->call('POST', '/login', [
            'username' => $username,
            'password' => $password,
            '_token' => csrf_token()
        ]);
    }
    /**
     * These api endpoints can only work if you are authenticated
     */
    public function testUnauthenticated()
    {
        $response = $this->api('GET', self::API_TEST_USERS);
        $response->assertStatus(401);
    }

    /**
     * Ensure our API endpoint is protected by required permission
     */
    public function testUnauthorized()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id' => factory(Role::class)->make(),
        ]);
        // No role means it should not be authorized
        $this->auth($user->username, 'password');
        $response = $this->api('GET', self::API_TEST_USERS);
        $response->assertStatus(403);
    }

    /**
     * Verify users listing is working with no filters provided
     */
    public function testUsersListing()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        // Build a sample of 5 users into the system
        $users = factory(User::class, 5)->create();
        // Fetch via API
        $response = $this->api('GET', self::API_TEST_USERS);
        // Verify 200 status code
        $response->assertStatus(200);
        // Grab users
        $data = json_decode($response->getContent(), true);
        // Verify we have a total of 9 results (our 5 plus admin plus our created user)
        $this->assertCount(9, $data['data']);
        $this->assertEquals(9, $data['meta']['total']);
        // Not testing returned data format as we're assuming the single user fetch validates that 
        // output matches transformer
    }

    /**
     * Test to ensure our filters are working and not matching on invalid filter criteria
     */
    public function testUserSearchNoMatches()
    {
        $user = factory(User::class)->create([
            'username' => 'testuser',
            'firstname' => 'Joe',
            'lastname' => 'Biden',
            'password' => Hash::make('password'),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $response = $this->api('GET', self::API_TEST_USERS . '?filter=' . urlencode('invalid'));
        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);
        // Ensure we have empty results
        $this->assertCount(0, $data['data']);
        $this->assertEquals(0, $data['meta']['total']);
        $this->assertEquals('invalid', $data['meta']['filter']);
    }

    /**
     * Test to ensure our filter is working for user search
     */
    public function testFilterMatch()
    {
        $user = factory(User::class)->create([
            'username' => 'testuser',
            'firstname' => 'UniqueJoe',
            'lastname' => 'Biden',
            'password' => Hash::make('password'),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $response = $this->api('GET', self::API_TEST_USERS . '?filter=' . urlencode('UniqueJoe'));
        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);
        // Ensure we have empty results
        $this->assertCount(1, $data['data']);
        $this->assertEquals(1, $data['meta']['total']);
        $this->assertEquals('UniqueJoe', $data['meta']['filter']);
        // We refetch from the DB to ensure we have all columns from DB when transforming
        $transformed = (new UserTransformer())->transform(User::find($user->id));
        $this->assertEquals($transformed, $data['data'][0]);
    }

    /**
     * Test to ensure we receive a 404 when passing in a uid not found
     */
    public function testUserNotFound()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $response = $this->api('get', self::API_TEST_USERS . '/invaliduid');
        $response->assertStatus(404);
    }

    /**
     * Test fetch single user fetch from api
     */
    public function testUserGet()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $response = $this->api('get', self::API_TEST_USERS . '/' . $user->uid->toString());
        $response->assertStatus(200);
        // Get our expected transformed user
        $expected = (new UserTransformer())->transform($user->refresh());
        // Now verify that's what we got
        $response->assertJson($expected);
    }

    /**
     * Get Profile user
     */
    public function testGetProfile()
    {
        $avatar = Faker::create()->image(Storage::disk('profile')->getAdapter()->getPathPrefix(), 10, 10, null, true);
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);
        $this->auth($user->username, 'password');

        $user->addMedia(public_path() . '/img/avatar.png')
            ->preservingOriginal()
            ->toMediaCollection(User::COLLECTION_PROFILE, User::DISK_PROFILE);

        $response = $this->api('get', self::API_TEST_PROFILE . 'profile');

        $response->assertStatus(200);
        $this->assertNotNull($response->json(['avatar']));
    }

    /**
     * Upload file in profile user.
     */
    public function testUploadAvatarProfile()
    {
        $diskName = User::DISK_PROFILE;
        Storage::disk($diskName);
        $nameAvatar = 'avatar.jpg';
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');

        $response = $this->api('put', self::API_TEST_PROFILE . 'profile', [
            'avatar' => UploadedFile::fake()->image($nameAvatar)
        ]);
        $response->assertStatus(200);

        $mediaAvatar = $user->getMedia($diskName);

        //verify name of file
        $this->assertEquals($nameAvatar, $mediaAvatar[0]->file_name);

        //get path of file
        $path = $mediaAvatar[0]->getPath();
        $path = explode($diskName, $path);

        //verify exist file
        Storage::disk($diskName)->assertExists($path[1]);
    }

    /**
     * Update information user
     */
    public function testUpdateUser()
    {
        $diskName = User::DISK_PROFILE;
        Storage::disk($diskName);
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $user = factory(User::class)->create([
            'uid' => '1234',
            'username' => app()->make('Faker\Generator')->text(10),
            'firstname' => app()->make('Faker\Generator')->text(10),
            'lastname' => app()->make('Faker\Generator')->text(10),
        ]);
        $response = $this->api('get', self::API_TEST_USERS, []);
        $response->assertStatus(200);
        $response = $this->api('put', self::API_TEST_USERS . '/' . $user->uid, [
            'firstname' => 'User update',
            'status' => 'ACTIVE',
            'lastname' => 'profile',
            'username' => $user->username,
            'password' => '1234',
        ]);
        $response->assertStatus(200);
        // Verify change made it to the database
        $this->assertDatabaseHas('users', [
            'uid' => $user->uid,
            'username' => $user->username,
            'firstname' => 'User update',
            'lastname' => 'profile',
            'status' => 'ACTIVE'
        ]);
        // Also ensure the changed attributes are reflected in JSON response
        $response->assertJson([
            'uid' => $user->uid,
            'username' => $user->username,
            'firstname' => 'User update',
            'lastname' => 'profile',
            'status' => 'ACTIVE'
        ]);
        //log back in with new password
        $response = $this->call('POST', '/login', [
            'username' => $user->username,
            'password' => '1234',
            '_token' => csrf_token()
        ]);
        $response->assertStatus(302);
    }
    /**
     * test user cannot login when inactive
     */
     public function testInactiveUserCantLogIn()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $user = factory(User::class)->create([
            'uid' => '1234',
            'username' => app()->make('Faker\Generator')->text(10),
            'firstname' => app()->make('Faker\Generator')->text(10),
            'lastname' => app()->make('Faker\Generator')->text(10),
            'status' => 'ACTIVE'
        ]);
        $response = $this->api('get', self::API_TEST_USERS, []);
        $response->assertStatus(200);
        $response = $this->api('put', self::API_TEST_USERS . '/' . $user->uid, [
            'firstname' => $user->firstname,
            'status' => 'INACTIVE',
            'lastname' => $user->lastname,
            'username' => $user->username,
            'password' => $user->password,
        ]);
        $response->assertStatus(200);
        $response=$this->doLogin($user->username,'uauau');
        //@TODO this is redirecting with a bad password as well as being able to log in as an inactive user
    }

    public function testCreateUser()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $data = [
            'username' => 'testuser',
            'firstname' => 'Test',
            'lastname' => 'User',
            'password' => 'password'
        ];

        $response = $this->api('post', self::API_TEST_USERS, $data);
        $response->assertStatus(200);
        unset($data['password']);
        $this->assertDatabaseHas('users', $data);	
        // Also check for duplicate user error
        // Just resubmit with same data
        $data['password'] = 'password';
        $response = $this->api('post', self::API_TEST_USERS, $data);
        $response->assertStatus(422);
        // Get a 422 with empty payload, with required fields being listed
        $data = [];
        $response = $this->api('post', self::API_TEST_USERS, $data);
        $response->assertStatus(422);
        // Check for hashed value for password
        $existingUser = User::where('username', 'testuser')->first();
        $this->assertEquals(true, Hash::check('password', $existingUser->password));
    }

    /**
     * Delete User
     */
    public function testDeleteUser()
    {
        $admin = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($admin->username, 'password');

        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);

        $response = $this->api('delete', self::API_TEST_USERS . '/' . $user->uid);
        $response->assertStatus(204);

        //validating that the user does not exist
        $existingUser = User::where('uid', $user->uid)->first();
        $this->assertNull($existingUser);
    }

}
