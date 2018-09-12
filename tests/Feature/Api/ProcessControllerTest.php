<?php
namespace Tests\Feature\Api;

use Faker\Factory as Faker;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use Tests\TestCase;

/**
 * Tests routes related to processes / CRUD related methods
 *
 */
class ProcessControllerTest extends TestCase
{

    const STRUCTURE = [
        'id',
        'type',
        'attributes' => [
            'uuid' ,
            'process_category_uuid',
            'user_uuid',
            'description',
            'name',
            'status',
            'created_at',
            'updated_at'
        ]
    ];

    /**
     * Test to verify our processes listing api endpoint works without any filters
     */
    public function testProcessesListing()
    {
        Process::query()->delete();
        $user = $this->authenticateAsAdmin();
        // Create some processes
        $faker = Faker::create();
        $numProcess = $faker->randomDigitNotNull;
        factory(Process::class, $numProcess)->create();
        $response = $this->actingAs($user, 'api')->json('GET', route('processes.index'));
        $response->assertStatus(200);
        // Verify we have a total of 5 results
        $this->assertCount($numProcess, $response->json('data'));
        $this->assertEquals($numProcess, $response->json('meta')['total']);
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * Test to verify our processes listing api endpoint works without any filters
     */
    public function testFiltering()
    {
        //clear process
        Process::query()->delete();
        $faker = Faker::create();
        $user = $this->authenticateAsAdmin();

        // Create some processes
        $processActive = [
            'num' => $faker->randomDigitNotNull,
            'status' => 'ACTIVE'
        ];

        $processInactive = [
            'num' => $faker->randomDigitNotNull,
            'status' => 'INACTIVE'
        ];
        factory(Process::class, $processActive['num'])->create(['status' => $processActive['status']]);
        factory(Process::class, $processInactive['num'])->create(['status' => $processInactive['status']]);
        $processRandom = $faker->randomElement([$processActive, $processInactive]);
        $response = $this->actingAs($user, 'api')->json('GET', route('processes.index')
            . '?filter=' . $processRandom["status"] .'&include=category,category.processes');
        $response->assertStatus(200);

        //dd($response->json('data'));
        //verify count of data
        $this->assertCount($processRandom['num'], $response->json('data'));

        //Verify the structure
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
        //verify include
        $response->assertJsonStructure(['*' => ['relationships' => ['category']]], $response->json('data'));
    }

    /**
     * Test to verify our processes listing api endpoint works with sorting
     */
    public function testSorting()
    {
        $user = $this->authenticateAsAdmin();
        // Create some processes
        factory(Process::class)->create([
            'name' => 'aaaaaa',
            'description' => 'bbbbbb'
        ]);
        factory(Process::class)->create([
            'name' => 'zzzzz',
            'description' => 'yyyyy'
        ]);
        $response = $this->actingAs($user, 'api')->json('GET', route('processes.index')
            . '?order_by=name&order_direction=asc');
        $response->assertStatus(200);
        $data = $response->json('data')[0]['attributes'];
        $this->assertEquals('aaaaaa', $data['name']);
        $this->assertEquals('bbbbbb', $data['description']);

        $response = $this->actingAs($user, 'api')->json('GET', route('processes.index')
            . '?order_by=name&order_direction=DESC');
        $response->assertStatus(200);
        $data = $response->json('data')[0]['attributes'];
        $this->assertEquals('zzzzz', $data['name']);
        $this->assertEquals('yyyyy', $data['description']);

        $response = $this->actingAs($user, 'api')->json('GET', route('processes.index')
            . '?order_by=description&order_direction=desc');
        $response->assertStatus(200);
        $data = $response->json('data')[0]['attributes'];
        $this->assertEquals('zzzzz', $data['name']);
        $this->assertEquals('yyyyy', $data['description']);
    }

    /**
     * Create an login API as an administrator user.
     *
     * @return User
     */
    private function authenticateAsAdmin(): User
    {
        $admin = factory(User::class)->create([]);
        return $admin;
    }
}
