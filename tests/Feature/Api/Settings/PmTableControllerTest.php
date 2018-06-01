<?php

namespace Tests\Feature\Api\Settings;

use Ramsey\Uuid\Uuid;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Facades\SchemaManager;
use ProcessMaker\Model\PmTable;
use ProcessMaker\Model\User;
use ProcessMaker\Model\Role;
use Tests\Feature\Api\ApiTestCase;

class PmTableControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    const API_TEST_PM_TABLES = '/api/1.0/pmtable';

    /**
     * Overwrite of the setup method that authenticates and fills the default connection data
     */
    protected function setUp()
    {
        parent::setUp();

        // we need an user and authenticate him
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);

        $this->auth($user->username, 'password');
    }

    /**
     * Tests the addition of a PmTable
     */
    public function testCreateOnePmTable(): void
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
        $pmInputData = $this->pmInputDefaultData();
        $response = $this->api('POST', self::API_TEST_PM_TABLES, $pmInputData);
        $response->assertStatus(201);

        $returnedObject = json_decode($response->getContent());
        $this->assertTrue($returnedObject->name === $pmInputData['name'],
            'The added pmTable has not the passed name');

        $this->assertGreaterThanOrEqual(0, count($returnedObject->fields),
            'The added pmTable must have fields');
    }

    /**
     * Tests the update of a PmTable
     */
    public function testUpdateOnePmTable()
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );

        $pmTable = $this->createTestPmTable();
        $description = 'Changed Description';

        // we update the pmTable
        $url = "/api/1.0/pmtable/" . $pmTable->uid;
        $response = $this->api('PUT', $url, ['description' => $description]);
        $response->assertStatus(200);
        $returnedObject = json_decode($response->getContent());
        $this->assertEquals($returnedObject->description, $description);

        $url = "/api/1.0/pmtable/" . $pmTable->uid;
        $response = $this->api('PUT', $url, [
            'description' => $description,
            'fields' => [
                [
                    'name' => 'NewField',
                    'description' => 'Field2 description',
                    'type' => 'VARCHAR',
                    'size' => '100',
                    'null' => 1,
                    'key' => 0,
                    'auto_increment' => 0,
                ]
            ]]);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'name',
            'grid',
            'db_source_id',
            'fields' => ['*' => ['name', 'type']]
        ]);

        $returnedObject = json_decode($response->getContent());
        $this->assertEquals($returnedObject->description, $description);
    }

    /**
     * Tests the return of the list of all PmTables
     */
    public function testGetAllPmTables()
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        $this->createTestPmTable();
        $url = "/api/1.0/pmtable";
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $returnedList = json_decode($response->getContent());
        $this->assertGreaterThanOrEqual(1, count($returnedList),
            'At least one additional PmTable should exist');
        $this->assertGreaterThanOrEqual(1, count($returnedList[0]->fields),
            'The returned test PmTable must have fields ');
    }

    /**
     * Tests the endpoint that gets one pmTable from the database
     */
    public function testShowOnePmTable()
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        $pmTable = $this->createTestPmTable();

        // we retrieve the pmTable with the endpoint
        $url = "/api/1.0/pmtable/" . $pmTable->uid;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $returnedModel = json_decode($response->getContent());

        $this->assertEquals($returnedModel->uid, $pmTable->uid,
            'The created test pmTable should be returned by the endpoint');

        $this->assertGreaterThanOrEqual(1, count($returnedModel->fields),
            'The returned test PmTable must have fields ');

        $physicalTableName = $pmTable->physicalTableName();
        $this->assertTrue(Schema::hasTable($physicalTableName), 'The PmTable was not created');
    }



    /**
     * Tests the deletion of a PmTable
     */
    public function testDeletePmTable()
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        $pmTable = $this->createTestPmTable();

        $numSourcesBefore = PmTable::count();
        $url = "/api/1.0/pmtable/" . $pmTable->uid;
        $response = $this->api('DELETE', $url);
        $numSourcesAfter = PmTable::count();
        $response->assertStatus(204);
        $this->assertEquals($numSourcesBefore, $numSourcesAfter + 1);
    }

    /**
     * Test get all the data of a pmTable
     */
    public function testGetAllData()
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        $pmTable = $this->createTestPmTable();

        $url = "/api/1.0/pmtable/" . $pmTable->uid . "/data";
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
    }

    /**
     * Test to add data to a PmTable
     */
    public function testAddDataRow()
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        $pmTable = $this->createTestPmTable();

        $dataRow = [
            'StringField' => 'string field',
            'TextField' => 'a text'
        ];

        $url = "/api/1.0/pmtable/" . $pmTable->uid . "/data";
        $response = $this->api('POST', $url, $dataRow);
        $response->assertStatus(201);
    }

    /**
     * Test to update a row in the PmTable
     */
    public function testUpdateDataRow()
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        $pmTable = $this->createTestPmTable();

        $dataRow = [
            'StringField' => 'string field',
            'TextField' => 'Text field changed'
        ];

        $url = "/api/1.0/pmtable/" . $pmTable->uid . "/data";
        $response = $this->api('POST', $url, $dataRow);
        $response->assertStatus(201);

        $lastItem = DB::table($pmTable->physicalTableName())->orderBy('IntegerField', 'desc')->first();

        $updateData = [
            'StringField' => 'string updated',
            'IntegerField' => $lastItem->IntegerField,
            'TextField' => 'text field updated'
        ];

        $url = "/api/1.0/pmtable/" . $pmTable->uid . "/data";
        $response = $this->api('PUT', $url, $updateData);
        $response->assertStatus(200);
    }

    /**
     * Test deletion of a row
     */
    public function testDeleteDataRow()
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        $pmTable = $this->createTestPmTable();

        $dataRow = [
            'StringField' => 'String1',
            'TextField' => 'Text1'
        ];

        // Add data to the PmTable
        $url = "/api/1.0/pmtable/" . $pmTable->uid . "/data";
        $response = $this->api('POST', $url, $dataRow);
        $response->assertStatus(201);

        //we need to the the id of the added row
        $insertedRow = (array)DB::table('PMT_TESTPMTABLE')
            ->orderBy('IntegerField', 'desc')
            ->first();
        $lastId = $insertedRow['IntegerField'];

        //a deletion with no keys must return an error
        $response = $this->api('DELETE', $url);
        $response->assertStatus(405);

        $numberRowsBefore = count($pmTable->allDataRows());
        $url = "/api/1.0/pmtable/" . $pmTable->uid . "/data/StringField/String1/TextField/Text1/IntegerField/" . $lastId;
        $response = $this->api('DELETE', $url);
        $numberRowsAfter = count($pmTable->allDataRows());
        $response->assertStatus(204);
        $this->assertEquals($numberRowsBefore - 1, $numberRowsAfter, "After the deletion the PmTable must have on less dataRow.");
    }



    /**
     * Returns the data that will be used in the tests for the creation of a PmTable
     *
     * @return array
     */
    private function pmInputDefaultData()
    {
        return [
            'name' => 'TestPmTable',
            'description' => 'Table Description',
            'type' => 'PMTABLE',
            'fields' => [
                [
                    'name' => 'Field1',
                    'description' => 'Field1 description',
                    'type' => 'INTEGER',
                    'null' => 1,
                    'table_index' => 1,
                    'auto_increment' => 0,
                    'key' => 0,
                ],
                [
                    'name' => 'Field2',
                    'description' => 'Field2 description',
                    'type' => 'VARCHAR',
                    'size' => '100',
                    'null' => 1,
                    'key' => 0,
                    'table_index' => 2,
                    'auto_increment' => 0,
                ]
            ]
        ];
    }

    /**
     * Creates a PmTable that will be used in the tests
     *
     * @return mixed
     */
    private function createTestPmTable()
    {
        // we create a new pmTable
        $pmTable = factory(PmTable::class)->create();

        $field1 = [
            'name' => 'StringField',
            'description' => 'String Field',
            'type' => 'VARCHAR',
            'size' => 250,
            'null' => 1
        ];

        $field2 = [
            'name' => 'IntegerField',
            'description' => 'Integer Field',
            'type' => 'INTEGER',
            'null' => 0,
            'key' => 1,
            'auto_increment' => 1
        ];

        $field3 = [
            'name' => 'TextField',
            'description' => 'Text Field',
            'type' => 'TEXT',
            'null' => 1
        ];

        SchemaManager::dropPhysicalTable('PMT_TESTPMTABLE');
        SchemaManager::updateOrCreateColumn($pmTable, $field1);
        SchemaManager::updateOrCreateColumn($pmTable, $field2);
        SchemaManager::updateOrCreateColumn($pmTable, $field3);

        return $pmTable;
    }
}