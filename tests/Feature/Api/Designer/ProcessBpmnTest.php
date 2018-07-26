<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessCategory;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;
use ProcessMaker\Transformers\ProcessTransformer;

/**
 * Tests routes related to processes / CRUD related methods
 *
 */
class ProcessesBpmnTest extends ApiTestCase
{
    use DatabaseTransactions;

    const API_TEST_PROCESS_BPMN = '/api/1.0/processes/{processUid}/bpmn';
    const API_TEST_PROCESS_UID = 'test-uid';

    /**
     * Tests to determine that reaching the processes endpoint is protected by an authenticated user
     */
    public function testUnauthenticated()
    {
        // Not creating a user, not logging in
        // Now attempt to connect to api

        $uri = str_replace('{processUid}', self::API_TEST_PROCESS_UID, self::API_TEST_PROCESS_BPMN);
        $response = $this->api('GET', $uri);
        $response->assertStatus(401);
    }

    /**
     * Test to verify that the bpm data of a process is returned
     */
    public function testProcessesGetBpmn(): void
    {
        $this->authenticateAsAdmin();
        // Create some processes
        factory(Process::class)->create([
            'uid' => self::API_TEST_PROCESS_UID,
            'bpmn' => '<?xml version="1.0" encoding="UTF-8"?><definitions xmlns="http://www.omg.org/spec/BPMN/20100524/MODEL" ></definitions>>'
        ]);

        $uri = str_replace('{processUid}', self::API_TEST_PROCESS_UID, self::API_TEST_PROCESS_BPMN);
        $response = $this->api('GET', $uri);
        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->getContent(), 'xml'));
    }

    /**
     * Test to verify that the bpm data of a process is updated
     */
    public function testProcessesPutBpmn(): void
    {
        $this->authenticateAsAdmin();

        // Create some processes
        factory(Process::class)->create([
            'uid' => self::API_TEST_PROCESS_UID
        ]);

        $processInDb = Process::where('uid', '=', self::API_TEST_PROCESS_UID)->first();

        // When created, a process shouln't have a bpmn value
        $this->assertEmpty($processInDb->bpmn);

        $bpmn =  'test-bpm-text';
        $uri = str_replace('{processUid}', self::API_TEST_PROCESS_UID, self::API_TEST_PROCESS_BPMN);
        $response = $this->api('patch', $uri, ['bpmn' => $bpmn]);
        $response->assertStatus(204);

        $processInDb = Process::where('uid', '=', self::API_TEST_PROCESS_UID)->first();

        // The updated process should have the bpmn column set
        $this->assertEquals($bpmn, $processInDb->bpmn);
    }

    /**
     * Create an login API as an administrator user.
     *
     * @return User
     */
    private function authenticateAsAdmin(): User
    {
        $admin = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);
        $this->auth($admin->username, 'password');
        return $admin;
    }
}
