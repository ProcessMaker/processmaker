<?php

namespace Tests\Feature\Api\Settings;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Facades\SchemaManager;
use ProcessMaker\Model\DbSource;
use ProcessMaker\Model\Form;
use ProcessMaker\Model\PmTable;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\User;
use ProcessMaker\Model\Role;
use Tests\Feature\Api\ApiTestCase;

class PmTableControllerTest extends ApiTestCase
{
    use DatabaseMigrations;

    /**
     * @var PmTable $pmTable
     */
    protected $pmTable;

    const API_TEST_PM_TABLES = '/api/1.0/pmtable/';

    const STRUCTURE = [
        'name',
        'description',
        'type',
        'uid',
        'fields'
    ];

    const STRUCTURE_FIELD = [
        'name',
        'description',
        'type',
        'size',
        'null',
        'auto_increment',
        'table_index',
        'key',
    ];

    /**
     * Overwrite of the setup method that authenticates and fills the default connection data
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        // we need an user and authenticate him
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);

        $this->auth($user->username, 'password');
        $this->pmTable = $this->createTestPmTable();
    }

    /**
     * Tests the addition of a PmTable
     */
    public function testCreatePmTable(): void
    {
        $response = $this->api('POST', self::API_TEST_PM_TABLES, $this->pmInputDefaultData());
        $response->assertStatus(201);

        $response->assertJsonStructure(self::STRUCTURE);
        $this->assertCount(3, $response->json('fields'));
    }

    /**
     * Tests the update of a PmTable
     */
    public function testUpdatePmTable(): void
    {
        $description = 'Changed Description';

        $url = self::API_TEST_PM_TABLES . $this->pmTable->uid;
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
        $response->assertJsonStructure(self::STRUCTURE);
        $response->assertJsonStructure(['*' => self::STRUCTURE_FIELD], $response->json('fields'));
        $this->assertEquals($description, $response->json('description'));
    }

    /**
     * Tests the return of the list of all PmTables
     */
    public function testGetAllPmTables(): void
    {
        $response = $this->api('GET', self::API_TEST_PM_TABLES);
        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')),
            'At least one additional PmTable should exist');
        $this->assertGreaterThanOrEqual(1, count($response->json('data')[0]['fields']),
            'The returned test PmTable must have fields ');
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
        $response->assertJsonStructure(['*' => self::STRUCTURE_FIELD], $response->json('data')[0]['fields']);
    }

    /**
     * Tests the return of the list of all PmTables
     */
    public function testGetAllPmTablesWhitFilter(): void
    {
        $perPage = Faker::create()->randomDigitNotNull;
        $query = '?current_page=1&per_page=' . $perPage . '&sort_by=name&sort_order=DESC&filter=' . urlencode($this->pmTable->name);
        $response = $this->api('GET', self::API_TEST_PM_TABLES . $query);
        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')),
            'At least one additional PmTable should exist');
        $this->assertGreaterThanOrEqual(1, count($response->json('data')[0]['fields']),
            'The returned test PmTable must have fields ');
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
        $response->assertJsonStructure(['*' => self::STRUCTURE_FIELD], $response->json('data')[0]['fields']);
    }

    /**
     * Tests the endpoint that gets one pmTable from the database
     */
    public function testShowOnePmTable(): void
    {
        // we retrieve the pmTable with the endpoint
        $url = self::API_TEST_PM_TABLES . $this->pmTable->uid;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $response->assertJsonStructure(self::STRUCTURE, $response->json());
        $response->assertJsonStructure(['*' => self::STRUCTURE_FIELD], $response->json('fields'));
    }


    /**
     * Tests the deletion of a PmTable
     */
    public function testDeletePmTable(): void
    {
        $numSourcesBefore = PmTable::count();
        $url = self::API_TEST_PM_TABLES . $this->pmTable->uid;
        $response = $this->api('DELETE', $url);
        $response->assertStatus(204);
        $this->assertEquals($numSourcesBefore, PmTable::count() + 1);
    }

    /**
     * Test get all the data of a pmTable
     */
    public function testGetAllData(): void
    {
        DB::table($this->pmTable->physicalTableName())
            ->insert([
                'StringField' => 'string field',
                'TextField' => 'a text'
            ]);
        $url = self::API_TEST_PM_TABLES . $this->pmTable->uid . '/data';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
    }

    /**
     * Test to add data to a PmTable
     */
    public function testAddDataRow(): void
    {
        $url = self::API_TEST_PM_TABLES . $this->pmTable->uid . '/data';
        $response = $this->api('POST', $url, [
            'StringField' => 'string field',
            'TextField' => 'a text'
        ]);
        $response->assertStatus(201);
    }

    /**
     * Test to update a row in the PmTable
     */
    public function testUpdateDataRow(): void
    {
        DB::table($this->pmTable->physicalTableName())
            ->insert([
                'StringField' => 'string field',
                'TextField' => 'a text'
            ]);


        $url = self::API_TEST_PM_TABLES . $this->pmTable->uid . '/data';
        $text = 'string updated';
        $text2 = 'text field updated';
        $response = $this->api('PUT', $url, [
            'StringField' => $text,
            'IntegerField' => 1,
            'TextField' => $text2
        ]);
        $response->assertStatus(200);
        $this->assertEquals($text, $response->json('StringField'));
        $this->assertEquals($text2, $response->json('TextField'));
    }

    /**
     * Test deletion of a row
     */
    public function testDeleteDataRow(): void
    {
        $tableName = $this->pmTable->physicalTableName();
        DB::table($tableName)
            ->insert([
                'StringField' => 'String1',
                'TextField' => 'Text1'
            ]);
        DB::table($tableName)
            ->insert([
                'StringField' => 'String2',
                'TextField' => 'Text2'
            ]);

        $numberRowsBefore = count($this->pmTable->allDataRows());
        $url = self::API_TEST_PM_TABLES . $this->pmTable->uid . '/data/StringField/String1/TextField/Text1/IntegerField/1';
        $response = $this->api('DELETE', $url);
        $numberRowsAfter = count($this->pmTable->allDataRows());
        $response->assertStatus(204);
        $this->assertEquals($numberRowsBefore - 1, $numberRowsAfter, 'After the deletion the PmTable must have on less dataRow.');
    }


    /**
     * Returns the data that will be used in the tests for the creation of a PmTable
     *
     * @return array
     */
    private function pmInputDefaultData()
    {
        $pmTable = factory(PmTable::class)->make();
        $pmTable->db_source_uid = factory(DbSource::class)->create()->uid;
        $pmTable->process_uid = factory(Process::class)->create()->uid->toString();
        $pmTable->fields = [
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
            ],
            [
                'name' => 'Field3',
                'description' => 'Field2 description',
                'type' => 'VARCHAR',
                'size' => '100',
                'null' => 1,
                'key' => 0,
                'table_index' => 3,
                'auto_increment' => 0,
                'dynaform_uid' => factory(Form::class)->create()->uid
            ],

        ];
        return $pmTable->toArray();
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
        SchemaManager::updateOrCreateColumn($pmTable, [
            'name' => 'StringField',
            'description' => 'String Field',
            'type' => 'VARCHAR',
            'size' => 250,
            'null' => 1
        ]);
        SchemaManager::updateOrCreateColumn($pmTable, [
            'name' => 'IntegerField',
            'description' => 'Integer Field',
            'type' => 'INTEGER',
            'null' => 0,
            'key' => 1,
            'auto_increment' => 1
        ]);
        SchemaManager::updateOrCreateColumn($pmTable, [
            'name' => 'TextField',
            'description' => 'Text Field',
            'type' => 'TEXT',
            'null' => 1
        ]);

        return $pmTable;
    }

    protected function tearDown(): void
    {
        if ($this->getName() === 'testDeleteDataRow') {
            $this->artisan('migrate:fresh');
            $this->seed();
        }
    }
}