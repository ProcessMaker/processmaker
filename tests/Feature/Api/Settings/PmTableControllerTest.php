<?php

namespace Tests\Feature\Api\Settings;

use Ramsey\Uuid\Uuid;
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
    /**
     * Tests the return of the list of all PmTables
     */
    public function testGetAllPmTables()
    {
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
        $pmTable = $this->createTestPmTable();

        // we retrieve the pmTable with the endpoint
        $url = "/api/1.0/pmtable/" . $pmTable->ADD_TAB_UID;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $returnedModel = json_decode($response->getContent());

        $this->assertEquals($returnedModel->add_tab_uid, $pmTable->ADD_TAB_UID,
            'The created test pmTable should be returned by the endpoint');

        $this->assertGreaterThanOrEqual(1, count($returnedModel->fields),
            'The returned test PmTable must have fields ');

        $physicalTableName = $pmTable->physicalTableName();
        $this->assertTrue(Schema::hasTable($physicalTableName), 'The PmTable was not created');
    }

    /**
     * Tests the addition of a PmTable
     */
    public function testCreateOnePmTable()
    {
        $pmInputData = $this->pmInputDefaultData();
        $response = $this->api('POST', '/api/1.0/pmtable/', $pmInputData);
        $response->assertStatus(201);

        $returnedObject = json_decode($response->getContent());
        $this->assertTrue($returnedObject->add_tab_name === $pmInputData['add_tab_name'],
            'The added pmTable has not the passed name');

        $this->assertGreaterThanOrEqual(0, count($returnedObject->fields),
            'The added pmTable must have fields');
    }

    /**
     * Tests the update of a PmTable
     */
    public function testUpdateOnePmTable()
    {
        $pmTable = $this->createTestPmTable();
        $description = 'Changed Description';

        // we update the pmTable
        $url = "/api/1.0/pmtable/" . $pmTable->ADD_TAB_UID;
        $response = $this->api('PUT', $url, ['add_tab_description' => $description]);
        $response->assertStatus(200);
        $returnedObject = json_decode($response->getContent());
        $this->assertEquals($returnedObject->add_tab_description, $description);

        $url = "/api/1.0/pmtable/" . $pmTable->ADD_TAB_UID;
        $response = $this->api('PUT', $url, [
            'add_tab_description' => $description,
            'fields' => [
                [
                    'fld_name' => 'NewField',
                    'fld_description' => 'Field2 description',
                    'fld_type' => 'VARCHAR',
                    'fld_size' => '100',
                    'fld_null' => 1,
                    'fld_key' => 0,
                    'fld_auto_increment' => 0,
                ]
            ]]);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'add_tab_name',
            'add_tab_grid',
            'dbs_uid',
            'fields' => ['*' => ['FLD_NAME', 'FLD_TYPE']]
        ]);

        $returnedObject = json_decode($response->getContent());
        $this->assertEquals($returnedObject->add_tab_description, $description);
    }

    /**
     * Tests the deletion of a PmTable
     */
    public function testDeletePmTable()
    {
        $pmTable = $this->createTestPmTable();

        $numSourcesBefore = PmTable::count();
        $url = "/api/1.0/pmtable/" . $pmTable->ADD_TAB_UID;
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
        $pmTable = $this->createTestPmTable();

        $url = "/api/1.0/pmtable/" . $pmTable->ADD_TAB_UID . "/data";
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
    }

    /**
     * Test to add data to a PmTable
     */
    public function testAddDataRow()
    {
        $pmTable = $this->createTestPmTable();

        $dataRow = [
            'StringField' => 'string field',
            'TextField' => 'a text'
        ];

        $url = "/api/1.0/pmtable/" . $pmTable->ADD_TAB_UID . "/data";
        $response = $this->api('POST', $url, $dataRow);
        $response->assertStatus(201);
    }

    /**
     * Test to update a row in the PmTable
     */
    public function testUpdateDataRow()
    {
        $pmTable = $this->createTestPmTable();

        $dataRow = [
            'StringField' => 'string field',
            'TextField' => 'Text field changed'
        ];

        $url = "/api/1.0/pmtable/" . $pmTable->ADD_TAB_UID . "/data";
        $response = $this->api('POST', $url, $dataRow);
        $response->assertStatus(201);

        $lastItem = DB::table($pmTable->physicalTableName())->orderBy('IntegerField', 'desc')->first();

        $updateData = [
            'StringField' => 'string updated',
            'IntegerField' => $lastItem->IntegerField,
            'TextField' => 'text field updated'
        ];

        $url = "/api/1.0/pmtable/" . $pmTable->ADD_TAB_UID . "/data";
        $response = $this->api('PUT', $url, $updateData);
        $response->assertStatus(200);
    }

    /**
     * Test deletion of a row
     */
    public function testDeleteDataRow()
    {
        $pmTable = $this->createTestPmTable();

        $dataRow = [
            'StringField' => 'String1',
            'TextField' => 'Text1'
        ];

        // Add data to the PmTable
        $url = "/api/1.0/pmtable/" . $pmTable->ADD_TAB_UID . "/data";
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
        $url = "/api/1.0/pmtable/" . $pmTable->ADD_TAB_UID . "/data/StringField/String1/TextField/Text1/IntegerField/" . $lastId;
        $response = $this->api('DELETE', $url);
        $numberRowsAfter = count($pmTable->allDataRows());
        $response->assertStatus(204);
        $this->assertEquals($numberRowsBefore - 1, $numberRowsAfter, "After the deletion the PmTable must have on less dataRow.");
    }

    /**
     * Overwrite of the setup method that authenticates and fills the default connection data
     */
    protected function setUp()
    {
        parent::setUp();

        // we need an user and authenticate him
        $user = factory(User::class)->create([
            'USR_PASSWORD' => Hash::make('password'),
            'USR_ROLE' => Role::PROCESSMAKER_ADMIN
        ]);

        $this->auth($user->USR_USERNAME, 'password');
    }

    /**
     * Returns the data that will be used in the tests for the creation of a PmTable
     *
     * @return array
     */
    private function pmInputDefaultData()
    {
        return [
            'add_tab_name' => 'TestPmTable',
            'add_tab_description' => 'Table Description',
            'dbs_uid' => env('DB_DATABASE'),
            'add_tab_type' => 'NORMAL',
            'fields' => [
                [
                    'fld_name' => 'Field1',
                    'fld_description' => 'Field1 description',
                    'fld_type' => 'INTEGER',
                    'fld_null' => 1,
                    'fld_table_index' => 1,
                    'fld_auto_increment' => 0,
                    'fld_key' => 0,
                ],
                [
                    'fld_name' => 'Field2',
                    'fld_description' => 'Field2 description',
                    'fld_type' => 'VARCHAR',
                    'fld_size' => '100',
                    'fld_null' => 1,
                    'fld_key' => 0,
                    'fld_table_index' => 2,
                    'fld_auto_increment' => 0,
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
        $factoryTable = factory(PmTable::class)->create();

        $pmTable = PmTable::where('ADD_TAB_UID', $factoryTable->ADD_TAB_UID)->get()[0];

        $field1 = [
            'FLD_UID' => str_replace('-', '', Uuid::uuid4()),
            'ADD_TAB_UID' => $pmTable->ADD_TAB_UID,
            'FLD_NAME' => 'StringField',
            'FLD_DESCRIPTION' => 'String Field',
            'FLD_TYPE' => 'VARCHAR',
            'FLD_SIZE' => 250,
            'FLD_NULL' => 1
        ];

        $field2 = [
            'FLD_UID' => str_replace('-', '', Uuid::uuid4()),
            'ADD_TAB_UID' => $pmTable->ADD_TAB_UID,
            'FLD_NAME' => 'IntegerField',
            'FLD_DESCRIPTION' => 'Integer Field',
            'FLD_TYPE' => 'INTEGER',
            'FLD_NULL' => 0,
            'FLD_KEY' => 1,
            'FLD_AUTO_INCREMENT' => 1
        ];

        $field3 = [
            'FLD_UID' => str_replace('-', '', Uuid::uuid4()),
            'ADD_TAB_UID' => $pmTable->ADD_TAB_UID,
            'FLD_NAME' => 'TextField',
            'FLD_DESCRIPTION' => 'Text Field',
            'FLD_TYPE' => 'TEXT',
            'FLD_NULL' => 1
        ];

        SchemaManager::dropPhysicalTable('PMT_TESTPMTABLE');
        SchemaManager::updateOrCreateColumn($pmTable, $field1);
        SchemaManager::updateOrCreateColumn($pmTable, $field2);
        SchemaManager::updateOrCreateColumn($pmTable, $field3);

        return $pmTable;
    }
}