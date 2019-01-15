<?php

namespace Tests\Feature\Api;

use Faker\Factory as Faker;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\PermissionAssignment;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
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
        'created_at'
    ];

    public function testGetCommentListAdministrator()
    {
        $response = $this->apiCall('GET', self::API_TEST_URL);
        $response->assertStatus(200);

        $faker = Faker::create();

        $model = factory($faker->randomElement([
            ProcessRequestToken::class,
            ProcessRequest::class,
        ]))->create();

        factory(Comment::class, 10)->create([
            'commentable_id' => $model->getKey(),
            'commentable_type' => get_class($model),
            'hidden' => false
        ]);

        factory(Comment::class, 5)->create([
            'commentable_id' => $model->getKey(),
            'commentable_type' => get_class($model),
            'hidden' => true
        ]);

        $response = $this->apiCall('GET', self::API_TEST_URL);
        $json = $response->json('data');
        $this->assertCount(15, $json);
    }

    public function testGetCommentListNoAdministrator()
    {
        $faker = Faker::create();

        $model = factory($faker->randomElement([
            ProcessRequestToken::class,
            ProcessRequest::class,
        ]))->create();

        factory(Comment::class, 10)->create([
            'commentable_id' => $model->getKey(),
            'commentable_type' => get_class($model),
            'hidden' => false
        ]);

        factory(Comment::class, 5)->create([
            'commentable_id' => $model->getKey(),
            'commentable_type' => get_class($model),
            'hidden' => true
        ]);

        $this->user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'is_administrator' => false,
        ]);
        $permission = factory(Permission::class)->create(['guard_name' => 'comments.index']);

        factory(PermissionAssignment::class)->create([
            'permission_id' => $permission->id,
            'assignable_type' => User::class,
            'assignable_id' => $this->user->id,
        ]);

        $response = $this->apiCall('GET', self::API_TEST_URL);
        $json = $response->json('data');
        $this->assertCount(10, $json);
    }

    public function testGetCommentByType()
    {
        $model = factory(ProcessRequestToken::class)->create();

        factory(Comment::class, 10)->create([
            'commentable_id' => $model->getKey(),
            'commentable_type' => get_class($model),
            'hidden' => false
        ]);

        factory(Comment::class, 5)->create([
            'commentable_id' => $model->getKey(),
            'commentable_type' => get_class($model),
            'hidden' => true
        ]);

        $model2 = factory(ProcessRequest::class)->create();

        factory(Comment::class, 10)->create([
            'commentable_id' => $model2->getKey(),
            'commentable_type' => get_class($model2),
            'hidden' => false
        ]);

        factory(Comment::class, 5)->create([
            'commentable_id' => $model2->getKey(),
            'commentable_type' => get_class($model2),
            'hidden' => true
        ]);

        $this->user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'is_administrator' => false,
        ]);
        $permission = factory(Permission::class)->create(['guard_name' => 'comments.index']);

        factory(PermissionAssignment::class)->create([
            'permission_id' => $permission->id,
            'assignable_type' => User::class,
            'assignable_id' => $this->user->id,
        ]);

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
        $comment = factory(Comment::class)->make();

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
        $comment = factory(Comment::class)->create()->id;

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
        $url = self::API_TEST_URL . '/' . factory(Comment::class)->create()->id;
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
        $url = self::API_TEST_URL . '/' . factory(Comment::class)->make()->id;
        $response = $this->apiCall('DELETE', $url);

        //Validate the header status code
        $response->assertStatus(405);
    }

}
