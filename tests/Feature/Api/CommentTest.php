<?php

namespace Tests\Feature\Api;

use Database\Seeders\PermissionSeeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\AuthServiceProvider;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RequestHelper;

    const API_TEST_URL = '/comments';

    const STRUCTURE = [
        'id',
        'user_id',
        'commentable_id',
        'commentable_type',
        'subject',
        'body',
        'hidden',
        'type',
        'updated_at',
        'created_at',
    ];

    protected function withUserSetup()
    {
        // Seed the permissions table.
        Artisan::call('db:seed', ['--class' => 'PermissionSeeder']);

        // Reboot our AuthServiceProvider. This is necessary so that it can
        // pick up the new permissions and setup gates for each of them.
        $asp = new AuthServiceProvider(app());
        $asp->boot();
    }

    public function testGetCommentListAdministrator()
    {
        $response = $this->apiCall('GET', self::API_TEST_URL);
        $response->assertStatus(200);

        $faker = Faker::create();

        $model = factory($faker->randomElement([
            ProcessRequestToken::class,
            ProcessRequest::class,
        ]))->create();

        Comment::factory()->count(10)->create([
            'commentable_id' => $model->getKey(),
            'commentable_type' => get_class($model),
            'hidden' => false,
        ]);

        Comment::factory()->count(5)->create([
            'commentable_id' => $model->getKey(),
            'commentable_type' => get_class($model),
            'hidden' => true,
        ]);

        $response = $this->apiCall('GET', self::API_TEST_URL);
        $json = $response->json('data');
        $this->assertCount(15, $json);
    }

    public function testGetCommentListNoAdministrator()
    {
        $permission = 'view-comments';

        $faker = Faker::create();

        $model = factory($faker->randomElement([
            ProcessRequestToken::class,
            ProcessRequest::class,
        ]))->create();

        Comment::factory()->count(10)->create([
            'commentable_id' => $model->getKey(),
            'commentable_type' => get_class($model),
            'hidden' => false,
        ]);

        Comment::factory()->count(5)->create([
            'commentable_id' => $model->getKey(),
            'commentable_type' => get_class($model),
            'hidden' => true,
        ]);

        $this->user = User::factory()->create([
            'password' => Hash::make('password'),
            'is_administrator' => false,
        ]);

        $this->user->permissions()->attach(Permission::byName($permission)->id);

        $response = $this->apiCall('GET', self::API_TEST_URL);
        $json = $response->json('data');
        $this->assertCount(10, $json);
    }

    public function testGetCommentByType()
    {
        $permission = 'view-comments';

        $model = ProcessRequestToken::factory()->create();

        Comment::factory()->count(10)->create([
            'commentable_id' => $model->getKey(),
            'commentable_type' => get_class($model),
            'hidden' => false,
        ]);

        Comment::factory()->count(5)->create([
            'commentable_id' => $model->getKey(),
            'commentable_type' => get_class($model),
            'hidden' => true,
        ]);

        $model2 = ProcessRequest::factory()->create();

        Comment::factory()->count(10)->create([
            'commentable_id' => $model2->getKey(),
            'commentable_type' => get_class($model2),
            'hidden' => false,
        ]);

        Comment::factory()->count(5)->create([
            'commentable_id' => $model2->getKey(),
            'commentable_type' => get_class($model2),
            'hidden' => true,
        ]);

        $this->user = User::factory()->create([
            'password' => Hash::make('password'),
            'is_administrator' => false,
        ]);

        $this->user->permissions()->attach(Permission::byName($permission)->id);

        $response = $this->apiCall('GET', self::API_TEST_URL . '?commentable_type=' . get_class($model2));
        $json = $response->json('data');
        $this->assertCount(10, $json);
    }

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

    public function testCreateComment()
    {
        $comment = Comment::factory()->make();

        $response = $this->apiCall('POST', self::API_TEST_URL, $comment->toArray());

        //Validate the header status code
        $response->assertStatus(201);

        //Validate the header status code
        $response->assertStatus(201);

        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Get a comment
     */
    public function testGetComment()
    {
        //get the id from the factory
        $comment = Comment::factory()->create()->id;

        //load api
        $response = $this->apiCall('GET', self::API_TEST_URL . '/' . $comment);

        //Validate the status is correct
        $response->assertStatus(200);

        //verify structure
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Delete comment
     */
    public function testDeleteComment()
    {
        //Remove comment
        $url = self::API_TEST_URL . '/' . Comment::factory()->create(['user_id' => $this->user->getKey()])->id;
        $response = $this->apiCall('DELETE', $url);

        //Validate the header status code
        $response->assertStatus(204);
    }

    /**
     * The comment does not exist
     */
    public function testDeleteCommentNotExist()
    {
        //Comment not exist
        $url = self::API_TEST_URL . '/' . Comment::factory()->make()->id;
        $response = $this->apiCall('DELETE', $url);

        //Validate the header status code
        $response->assertStatus(405);
    }
}
