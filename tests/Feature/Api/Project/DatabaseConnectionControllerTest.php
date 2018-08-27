<?php
namespace Tests\Feature\Api\Project;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\DbSource;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\User;
use Tests\TestCase;

class DatabaseConnectionControllerTest extends TestCase
{
    use DatabaseTransactions;

    private $defaultConnectionData;

    public $user;

    /**
     * Tests the creation of a database connection
     */
    public function testCreateDataBaseConnection()
    {
        // We need a process
        $process = factory(Process::class)->create();

        $response = $this->actingAs($this->user, 'api')->json('POST', '/api/1.0/project/' . $process->uid . '/database-connection', []);
        $response->assertStatus(422);

        $dbConnectionInputData = $this->defaultConnectionData;
        $dbConnectionInputData['process_uid'] = $process->uid->toString();

        //a wrong db type should return 422
        $dbConnectionInputData['type'] = 'XYZWrongType';
        $response = $this->actingAs($this->user, 'api')->json('POST', '/api/1.0/project/' . $process->uid . '/database-connection', $dbConnectionInputData);
        $response->assertStatus(422);
        $dbConnectionInputData['type'] =  env('DB_ADAPTER');

        //an empty db server should return 422
        $dbConnectionInputData['server'] = '';
        $response = $this->actingAs($this->user, 'api')->json('POST', '/api/1.0/project/' . $process->uid . '/database-connection', $dbConnectionInputData);
        $response->assertStatus(422);
        $dbConnectionInputData['server'] = env('DB_HOST');

        //an empty db name should return 422
        $dbConnectionInputData['database_name'] = '';
        $response = $this->actingAs($this->user, 'api')->json('POST', '/api/1.0/project/' . $process->uid . '/database-connection', $dbConnectionInputData);
        $response->assertStatus(422);
        $dbConnectionInputData['database_name'] = env('DB_DATABASE');

        //an empty db name should return 422
        $dbConnectionInputData['port'] = '';
        $response = $this->actingAs($this->user, 'api')->json('POST', '/api/1.0/project/' . $process->uid . '/database-connection', $dbConnectionInputData);
        $response->assertStatus(422);
        $dbConnectionInputData['port'] = env('DB_PORT');

        //an oracle connection with empty tns should return 422
        $dbConnectionInputData['type'] = 'oracle';
        $dbConnectionInputData['tns'] = '';
        $dbConnectionInputData['connection_type'] = 'TNS';
        $response = $this->actingAs($this->user, 'api')->json('POST', '/api/1.0/project/' . $process->uid . '/database-connection', $dbConnectionInputData);
        $response->assertStatus(422);
        $dbConnectionInputData['type'] = env('DB_ADAPTER');

        //a wrong dbs_encode should return 422
        $dbConnectionInputData['encode'] = '';
        $response = $this->actingAs($this->user, 'api')->json('POST', '/api/1.0/project/' . $process->uid . '/database-connection', $dbConnectionInputData);
        $response->assertStatus(422);
        $dbConnectionInputData['encode'] = 'utf8';

        //valid information must be inserted so that the table will contain one more row
        $numSourcesBefore = DbSource::count();
        $response = $this->actingAs($this->user, 'api')->json('POST', '/api/1.0/project/' . $process->uid . '/database-connection', $dbConnectionInputData);
        $numSourcesAfter = DbSource::count();
        $returnedDbSource = json_decode($response->getContent());
        $response->assertStatus(200);
        $this->assertEquals($numSourcesBefore + 1, $numSourcesAfter);
        $this->assertTrue($returnedDbSource->description === $dbConnectionInputData['description'],
            'The returned dbsource should have the same data of the one added in the test and in lowercase');

        $this->assertTrue($returnedDbSource->description !== null,
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
        $url = "/api/1.0/project/$process->uid/database-connection/$dbSource->uid";
        $response = $this->actingAs($this->user, 'api')->json('PUT', $url, []);
        $response->assertStatus(422);

        $dbConnectionInputData = $this->defaultConnectionData;
        $dbConnectionInputData['process_uid'] = $process->uid->toString();

        //An update with a wrong server should return 422 error
        $url = "/api/1.0/project/$process->uid/database-connection/$dbSource->uid";
        $dbConnectionInputData['server'] = 'wrongServer';
        $response = $this->actingAs($this->user, 'api')->json('PUT', $url, $dbConnectionInputData);
        $response->assertStatus(422);
        $dbConnectionInputData['server'] = env('DB_HOST');

        //A call with correct parameters should return 200'
        $url = "/api/1.0/project/$process->uid/database-connection/$dbSource->uid";
        $response = $this->actingAs($this->user, 'api')->json('PUT', $url, $dbConnectionInputData);
        $response->assertStatus(200);

        $returnedDbSource = json_decode($response->getContent());
        $this->assertTrue($returnedDbSource->description === $dbConnectionInputData['description'],
            'The returned dbsource should have the same data of the one added in the test and in lowercase');

        $this->assertTrue($returnedDbSource->description !== null,
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
        $url = "/api/1.0/project/$process->uid/database-connection/$dbSource->uid";
        $response = $this->actingAs($this->user, 'api')->json('DELETE', $url);
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

        $url = "/api/1.0/project/{$dbSource->process->uid}/database-connections";
        $response = $this->actingAs($this->user, 'api')->json('GET', $url);
        $response->assertStatus(200);
        $returnedList = json_decode($response->getContent());
        $this->assertTrue(count($returnedList) === 1, 'The process has just one associated dbsource');

        // the returned list must be equal to the dbSource added and in lower case
        $this->assertTrue($returnedList[0]->uid === $dbSource->uid->toString());

        // a wrong process id should return 404
        $url = "/api/1.0/project/WRONGPROCESSID/database-connections";
        $response = $this->actingAs($this->user, 'api')->json('GET', $url);
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
        $url = "/api/1.0/project/{$dbSource->process->uid}/database-connection/$dbSource->uid";
        $response = $this->actingAs($this->user, 'api')->json('GET', $url);
        $response->assertStatus(200);
        $returnedDbSource = json_decode($response->getContent());
        $this->assertTrue($returnedDbSource->uid === $dbSource->uid->toString(),
                    'The returned dbsource should have the same data of the one added in the test and in lowercase');

        $this->assertTrue($returnedDbSource->description !== null,
                    'The returned dbsource should contain the calculated dbs_database_description attribute');

        // Negative test. It is validated that the dbsource pertains to the process
        // for this, we need another dbSource and process
        $dbSource2 = factory(DbSource::class)->create();

        // as the $dbSource2 do not pertains to the process created in $dbSource, a 404 error should be returned
        $url = "/api/1.0/project/{$dbSource->process->uid}/database-connection/$dbSource2->uid";
        $response = $this->actingAs($this->user, 'api')->json('GET', $url);
        $response->assertStatus(404);
    }

    /**
     * Tests if the fields of a Tns Connection are correctly set
     */
    public function testTheCorrectFormattingOfATnsConnection()
    {
        // we need a dbSource with tns configured
        $dbSource = factory(DbSource::class)->make();
        $dbSource->type =  'oracle';
        $dbSource->tns = 'this:is:a:fake:tsn';
        $dbSource->connection_type = 'TNS';
        $dbSource->saveOrFail();


        // get the created dbsource and assert if the fields server and database_name have the correct format
        $url = "/api/1.0/project/{$dbSource->process->uid}/database-connection/$dbSource->uid";
        $response = $this->actingAs($this->user, 'api')->json('GET', $url);
        $response->assertStatus(200);
        $returnedDbSource = json_decode($response->getContent());

        $this->assertTrue($returnedDbSource->server ===  '[' . $dbSource->tns . ']',
                            'The server must be closed in brackets [] in a Tns connection');

        $this->assertTrue($returnedDbSource->database_name ===  '[' . $dbSource->tns . ']',
                                'The database name must be closed in brackets [] in a Tns connection');

        // get all connections of a process and verify if the fields server and database_name have the correct format
        $url = "/api/1.0/project/{$dbSource->process->uid}/database-connections";
        $response = $this->actingAs($this->user, 'api')->json('GET', $url);
        $response->assertStatus(200);
        $returnedDbSource = json_decode($response->getContent());

        $this->assertTrue($returnedDbSource[0]->server ===  '[' . $dbSource->tns . ']',
            'The server must be closed in brackets [] in a Tns connection');

        $this->assertTrue($returnedDbSource[0]->database_name ===  '[' . $dbSource->tns . ']',
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
        $dbConnectionInputData['process_uid'] = $process->uid->toString();

        // a correct connection should return 200
        $response = $this->actingAs($this->user, 'api')->json('POST', '/api/1.0/project/' . $process->uid . '/database-connection/test', $dbConnectionInputData);
        $response->assertStatus(200);

        // a wrong type connection should return 422 error
        $dbConnectionInputData['type'] = 'WrongType';
        $response = $this->actingAs($this->user, 'api')->json('POST', '/api/1.0/project/' . $process->uid . '/database-connection/test', $dbConnectionInputData);
        $response->assertStatus(422);
        $dbConnectionInputData['type'] = 'mysql';

        // a  connection with a wrong server should return 422 error
        $dbConnectionInputData['server'] = 'WrongServer';
        $response = $this->actingAs($this->user, 'api')->json('POST', '/api/1.0/project/' . $process->uid . '/database-connection/test', $dbConnectionInputData);
        $response->assertStatus(422);
    }


    /**
     * Overwrite of the setup method that authenticates and fills the default connection data
     */
    protected function setUp()
    {
        parent::setUp();

        // we need an user and authenticate hime
        $this->user = factory(User::class)->create([
            'password' => Hash::make('password')
        ]);

        // we fill the default connection data to be used in the tests
        $this->defaultConnectionData = [
            'type'=> env('DB_ADAPTER'),
            'server'=> env('DB_HOST'),
            'database_name'=> env('DB_DATABASE'),
            'username'=> env('DB_USERNAME'),
            'password'=> env('DB_PASSWORD'),
            'port'=> env('DB_PORT'),
            'encode'=> 'utf8',
            'description'=> 'Connection for testing purposes'
        ];
    }

}
