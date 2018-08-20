<?php

namespace Tests\Feature\Api\Cases;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\DbSource;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessVariable;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class ProcessControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    /**
     * Tests that the view with the selected process is displayed
     */
    public function testShowDiagram()
    {
        $process = factory(Process::class)->create();

        $url = "/designer/" . $process->uid;

        // Calling the designer with a correct process id should run correctly
        $correctCall = $this->api('GET', $url);
        $correctCall->assertStatus(200);
        $this->assertFalse($correctCall->isRedirection());

        // Calling the designer with a wrong process id should redirect
        $url = "/designer/wrong-id";
        $nonExistentProcessCall = $this->api('GET', $url);
        $this->assertTrue($nonExistentProcessCall->isRedirection());

        // Calling the designer without a process is should redirect
        $url = "/designer";
        $noProcessIdCall = $this->api('GET', $url);
        $this->assertTrue($noProcessIdCall->isRedirection());
    }

    /**
     * Overwrite of the setup method that authenticates and fills the default connection data
     */
    protected function setUp()
    {
        parent::setUp();

        // we need an user and authenticate him
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id' => Role::PROCESSMAKER_ADMIN
        ]);

        $this->auth($user->username, 'password');
    }

}
