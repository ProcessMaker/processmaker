<?php
namespace Tests\Feature\Api\Project;

use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\DbSource;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\User;
use ProcessMaker\Model\Role;
use Tests\Feature\Api\ApiTestCase;

class DatabaseConnectionControllerTest extends ApiTestCase
{
    private $defaultConnectionData;

    /**
     * Tests the creation of a database connection
     */
    public function testCreateDataBaseConnection()
    {
        // We need a process
        $process = factory(Process::class)->create();

        $response = $this->api('POST', '/api/1.0/project/' . $process->PRO_UID . '/database-connection', []);
        $response->assertStatus(422);

        $dbConnectionInputData = $this->defaultConnectionData;
        $dbConnectionInputData['pro_uid'] = $process->PRO_UID;

        //a wrong db type should return 422
        $dbConnectionInputData['dbs_type'] = 'XYZWrongType';
        $response = $this->api('POST', '/api/1.0/project/' . $process->PRO_UID . '/database-connection', $dbConnectionInputData);
        $response->assertStatus(422);
        $dbConnectionInputData['dbs_type'] =  env('DB_ADAPTER');

        //an empty db server should return 422
        $dbConnectionInputData['dbs_server'] = '';
        $response = $this->api('POST', '/api/1.0/project/' . $process->PRO_UID . '/database-connection', $dbConnectionInputData);
        $response->assertStatus(422);
        $dbConnectionInputData['dbs_server'] = env('DB_HOST');

        //an empty db name should return 422
        $dbConnectionInputData['dbs_database_name'] = '';
        $response = $this->api('POST', '/api/1.0/project/' . $process->PRO_UID . '/database-connection', $dbConnectionInputData);
        $response->assertStatus(422);
        $dbConnectionInputData['dbs_database_name'] = env('DB_DATABASE');

        //an empty db name should return 422
        $dbConnectionInputData['dbs_port'] = '';
        $response = $this->api('POST', '/api/1.0/project/' . $process->PRO_UID . '/database-connection', $dbConnectionInputData);
        $response->assertStatus(422);
        $dbConnectionInputData['dbs_port'] = env('DB_PORT');

        //an oracle connection with empty tns should return 422
        $dbConnectionInputData['dbs_type'] = 'oracle';
        $dbConnectionInputData['dbs_tns'] = '';
        $dbConnectionInputData['dbs_connection_type'] = 'TNS';
        $response = $this->api('POST', '/api/1.0/project/' . $process->PRO_UID . '/database-connection', $dbConnectionInputData);
        $response->assertStatus(422);
        $dbConnectionInputData['dbs_type'] = env('DB_ADAPTER');

        //a wrong dbs_encode should return 422
        $dbConnectionInputData['dbs_encode'] = '';
        $response = $this->api('POST', '/api/1.0/project/' . $process->PRO_UID . '/database-connection', $dbConnectionInputData);
        $response->assertStatus(422);
        $dbConnectionInputData['dbs_encode'] = 'utf8';

        //valid information must be inserted so that the table will contain one more row
        $numSourcesBefore = DbSource::count();
        $response = $this->api('POST', '/api/1.0/project/' . $process->PRO_UID . '/database-connection', $dbConnectionInputData);
        $numSourcesAfter = DbSource::count();
        $returnedDbSource = json_decode($response->getContent());
        $response->assertStatus(200);
        $this->assertEquals($numSourcesBefore + 1, $numSourcesAfter);
        $this->assertTrue($returnedDbSource->dbs_description === $dbConnectionInputData['dbs_description'],
            'The returned dbsource should have the same data of the one added in the test and in lowercase');

        $this->assertTrue($returnedDbSource->dbs_database_description !== null,
            'The returned dbsource should contain the calculated dbs_database_description attribute');
    }

    /**
     * Tests the update of a database connection
     */
    public function testUpdateDataBaseConnection()
    {
        // We need a process
        $process = factory(Process::class)->create();

        // we need a dbSource
        $dbSource = factory(DbSource::class)->create();

        //If parameters are empty a 422 error should be sent')
        $url = "/api/1.0/project/$process->PRO_UID/database-connection/$dbSource->DBS_UID";
        $response = $this->api('PUT', $url, []);
        $response->assertStatus(422);

        $dbConnectionInputData = $this->defaultConnectionData;
        $dbConnectionInputData['pro_uid'] = $process->PRO_UID;

        //An update with a wrong server should return 422 error
        $url = "/api/1.0/project/$process->PRO_UID/database-connection/$dbSource->DBS_UID";
        $dbConnectionInputData['dbs_server'] = 'wrongServer';
        $response = $this->api('PUT', $url, $dbConnectionInputData);
        $response->assertStatus(422);
        $dbConnectionInputData['dbs_server'] = env('DB_HOST');

        //A call with correct parameters should return 200'
        $url = "/api/1.0/project/$process->PRO_UID/database-connection/$dbSource->DBS_UID";
        $response = $this->api('PUT', $url, $dbConnectionInputData);
        $response->assertStatus(200);

        $returnedDbSource = json_decode($response->getContent());
        $this->assertTrue($returnedDbSource->dbs_description === $dbConnectionInputData['dbs_description'],
            'The returned dbsource should have the same data of the one added in the test and in lowercase');

        $this->assertTrue($returnedDbSource->dbs_database_description !== null,
            'The returned dbsource should contain the calculated dbs_database_description attribute');
    }

    /**
     * Tests the deletion of a database connection
     */
    public function testDeleteDatabaseConnection()
    {
        // We need a process
        $process = factory(Process::class)->create();

        // we need a dbSource
        $dbSource = factory(DbSource::class)->create();

        //this is the url to use for the endpoint
        $numSourcesBefore = DbSource::count();
        $url = "/api/1.0/project/$process->PRO_UID/database-connection/$dbSource->DBS_UID";
        $response = $this->api('DELETE', $url);
        $numSourcesAfter = DbSource::count();
        $response->assertStatus(200);
        $this->assertEquals($numSourcesBefore, $numSourcesAfter + 1);
    }

    /**
     * Tests the return of the database connections of a process
     */
    public function testGetProcessDatabaseConnections()
    {
        // we need a dbSource (the factory creates a process too)
        $dbSource = factory(DbSource::class)->create();

        $url = "/api/1.0/project/$dbSource->PRO_UID/database-connections";
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $returnedList = json_decode($response->getContent());
        $this->assertTrue(count($returnedList) === 1, 'The process has just one associated dbsource');

        // the returned list must be equal to the dbSource added and in lower case
        $this->assertTrue($returnedList[0]->dbs_uid === $dbSource->DBS_UID);

        // a wrong process id should return 404
        $url = "/api/1.0/project/WRONGPROCESSID/database-connections";
        $response = $this->api('GET', $url);
        $response->assertStatus(404);
    }

    /**
     * Tests to get a database connection
     */
    public function testGetADatabaseConnectionsFromAProcess()
    {
        // we need a dbSource (the factory creates a process too)
        $dbSource = factory(DbSource::class)->create();

        // test if the created dbSource is the returned
        $url = "/api/1.0/project/$dbSource->PRO_UID/database-connection/$dbSource->DBS_UID";
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $returnedDbSource = json_decode($response->getContent());
        $this->assertTrue($returnedDbSource->dbs_uid === $dbSource->DBS_UID,
                    'The returned dbsource should have the same data of the one added in the test and in lowercase');

        $this->assertTrue($returnedDbSource->dbs_database_description !== null,
                    'The returned dbsource should contain the calculated dbs_database_description attribute');

        // Negative test. It is validated that the dbsource pertains to the process
        // for this, we need another dbSource and process
        $dbSource2 = factory(DbSource::class)->create();

        // as the $dbSource2 do not pertains to the process created in $dbSource, a 404 error should be returned
        $url = "/api/1.0/project/$dbSource->PRO_UID/database-connection/$dbSource2->DBS_UID";
        $response = $this->api('GET', $url);
        $response->assertStatus(404);
    }

    /**
     * Tests if the fields of a Tns Connection are correctly set
     */
    public function testTheCorrectFormattingOfATnsConnection()
    {
        // we need a dbSource with tns configured
        $dbSource = factory(DbSource::class)->make();
        $dbSource->DBS_TYPE =  'oracle';
        $dbSource->DBS_TNS = 'this:is:a:fake:tsn';
        $dbSource->DBS_CONNECTION_TYPE = 'TNS';
        $dbSource->saveOrFail();


        // get the created dbsource and assert if the fields server and database_name have the correct format
        $url = "/api/1.0/project/$dbSource->PRO_UID/database-connection/$dbSource->DBS_UID";
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $returnedDbSource = json_decode($response->getContent());

        $this->assertTrue($returnedDbSource->dbs_server ===  '[' . $dbSource->DBS_TNS . ']',
                            'The server must be closed in brackets [] in a Tns connection');

        $this->assertTrue($returnedDbSource->dbs_database_name ===  '[' . $dbSource->DBS_TNS . ']',
                                'The database name must be closed in brackets [] in a Tns connection');

        // get all connections of a process and verify if the fields server and database_name have the correct format
        $url = "/api/1.0/project/$dbSource->PRO_UID/database-connections";
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $returnedDbSource = json_decode($response->getContent());

        $this->assertTrue($returnedDbSource[0]->dbs_server ===  '[' . $dbSource->DBS_TNS . ']',
            'The server must be closed in brackets [] in a Tns connection');

        $this->assertTrue($returnedDbSource[0]->dbs_database_name ===  '[' . $dbSource->DBS_TNS . ']',
            'The database name must be closed in brackets [] in a Tns connection');
    }

    /**
     * Test if the connection testing works with correct and wrong parameters
     */
    public function testConnection()
    {
        // We need a process
        $process = factory(Process::class)->create();

        $dbConnectionInputData = $this->defaultConnectionData;
        $dbConnectionInputData['pro_uid'] = $process->PRO_UID;

        // a correct connection should return 200
        $response = $this->api('POST', '/api/1.0/project/' . $process->PRO_UID . '/database-connection/test', $dbConnectionInputData);
        $response->assertStatus(200);

        // a wrong type connection should return 422 error
        $dbConnectionInputData['dbs_type'] = 'WrongType';
        $response = $this->api('POST', '/api/1.0/project/' . $process->PRO_UID . '/database-connection/test', $dbConnectionInputData);
        $response->assertStatus(422);
        $dbConnectionInputData['dbs_type'] = 'mysql';

        // a  connection with a wrong server should return 422 error
        $dbConnectionInputData['dbs_server'] = 'WrongServer';
        $response = $this->api('POST', '/api/1.0/project/' . $process->PRO_UID . '/database-connection/test', $dbConnectionInputData);
        $response->assertStatus(422);
    }


    /**
     * Overwrite of the setup method that authenticates and fills the default connection data
     */
    protected function setUp()
    {
        parent::setUp();

        // we need an user and authenticate hime
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);

        $this->auth($user->username, 'password');

        // we fill the default connection data to be used in the tests
        $this->defaultConnectionData = [
            'dbs_type'=> env('DB_ADAPTER'),
            'dbs_server'=> env('DB_HOST'),
            'dbs_database_name'=> env('DB_DATABASE'),
            'dbs_username'=> env('DB_USERNAME'),
            'dbs_password'=> env('DB_PASSWORD'),
            'dbs_port'=> env('DB_PORT'),
            'dbs_encode'=> 'utf8',
            'dbs_description'=> 'Connection for testing purposes'
        ];
    }

}