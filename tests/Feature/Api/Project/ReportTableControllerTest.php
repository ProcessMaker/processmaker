<?php

namespace Tests\Unit;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
//use PHP_CodeSniffer\Reports\Report;
use ProcessMaker\Facades\SchemaManager;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\DbSource;
use ProcessMaker\Model\ProcessVariable;
use ProcessMaker\Model\ReportTable;
use ProcessMaker\Model\ReportTableColumn;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class ReportTableControllerTest extends ApiTestCase
{
    use DatabaseMigrations;

    /**
     * @var ReportTable $report
     */
    protected $report;

    const API_TEST_REPORT_TABLES = '/api/1.0/process/';

    const STRUCTURE = [
        'uid',
        'name',
        'connection',
        'description',
        'process',
        'type',
        'grid',
        'tag',
        'fields',
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
        $this->report = $this->createDefaultReportTable();
    }

    /**
     * Test to retrieve all report tables of a process
     */
    public function testGetAllReportTables(): void
    {
        $url = self::API_TEST_REPORT_TABLES . $this->report->process->uid . '/report-tables';

        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * Test to retrieve all report tables of a process with filter
     */
    public function testGetAllReportTablesWithFilter(): void
    {
        $perPage = Faker::create()->randomDigitNotNull;
        $query = '?current_page=1&per_page=' . $perPage . '&sort_by=name&sort_order=DESC&filter=' . urlencode($this->report->name);
        $url = self::API_TEST_REPORT_TABLES . $this->report->process->uid . '/report-tables' . $query;

        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
        $this->assertEquals($perPage, $response->original->meta->per_page);
    }

    /**
     * Test to retrieve on report table
     */
    public function testGetOneReportTable(): void
    {
        $url = self::API_TEST_REPORT_TABLES . $this->report->process->uid . '/report-table/' . $this->report->uid;

        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $response->assertJsonStructure(['name', 'grid', 'connection', 'fields']);
    }

    /**
     * Tests the addition of a report table
     */
    public function testCreateOneReportTable(): void
    {
        $reportJsonApi = $this->newReportTableData($this->report->process);

        $url = self::API_TEST_REPORT_TABLES . $this->report->process->uid . '/report-table';
        $response = $this->api('POST', $url, $reportJsonApi);
        $response->assertStatus(201);
        $returnedObject = json_decode($response->getContent());
        $response->assertJsonStructure([
            'name',
            'connection',
            'grid',
            'fields' => ['*' => ['name', 'type']]
        ]);

        $this->assertEquals($returnedObject->name, $reportJsonApi['name'], 'The added ReportTable has not the passed name');
        $this->assertGreaterThanOrEqual(0, count($returnedObject->fields), 'The added ReportTable must have fields');
    }

    /**
     * Test to update a report table
     */
    public function testUpdateOneReportTable(): void
    {
        $numberOfColumnsBeforeUpdate = count($this->report->fields);
        $description = 'Changed Description';

        // we update the pmTable
        $url = self::API_TEST_REPORT_TABLES . $this->report->process->uid . '/report-table/' . $this->report->uid;
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
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'name',
            'grid',
            'fields' => ['*' => ['name', 'type']]
        ]);

        $returnedObject = json_decode($response->getContent());
        $this->assertEquals($returnedObject->description, $description);
        $this->assertCount($numberOfColumnsBeforeUpdate + 1, $returnedObject->fields, 'The updated report table should have 1 additional field');
    }

    /**
     * Test to delete a report table
     */
    public function testDeleteReportTable(): void
    {
        $numSourcesBefore = ReportTable::count();

        $url = self::API_TEST_REPORT_TABLES . $this->report->process->uid . '/report-table/' . $this->report->uid;
        $response = $this->api('DELETE', $url);
        $numSourcesAfter = ReportTable::count();
        $response->assertStatus(204);
        $this->assertEquals($numSourcesBefore, $numSourcesAfter + 1);
    }

    /**
     * Test to populate a report table with the data of the instances of a process
     */
    public function testPopulateDataReport(): void
    {
        // we empty the list of instances
        DB::table('APPLICATION')->delete();

        $report = $this->createAndPopulateTestReportTable();
        $pmTable = $report->getAssociatedPmTable();

        // call to populate report table
        $url = self::API_TEST_REPORT_TABLES . $report->process->uid . '/report-table/' . $report->uid . '/populate';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);

        $repTableCount = DB::table($pmTable->physicalTableName())->count();
        $this->assertEquals(2, $repTableCount, 'the variables of 2 instances must be in the report table');
    }

    /**
     * Test to get all the data of a report table
     */
    public function testGetAllData(): void
    {
        $report = $this->createAndPopulateTestReportTable();
        $url = self::API_TEST_REPORT_TABLES . $report->process->uid . '/report-table/' . $report->uid . '/data';
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
    }

    /**
     * Creates a basic report table to be used in the tests
     *
     * @param string null $forceVariableTypesTo, name of the variable type that all the columns of the
     *  report table will have, it it is not set, a random type will be used
     *
     * @return ReportTable
     */
    private function createDefaultReportTable($forceVariableTypesTo = null): ReportTable
    {
        // we create a report table
        $newReport = factory(ReportTable::class)->create();

        $pmTable = $newReport->getAssociatedPmTable();

        // we add some variables to the report table
        $varsParams = [];
        $varsParams ['PRO_ID'] = $newReport->process->id;
        if ($forceVariableTypesTo !== null) {
            $varsParams['VAR_FIELD_TYPE'] = $forceVariableTypesTo;
        }

        $v1 = factory(ProcessVariable::class)->create($varsParams);
        $var1 = ProcessVariable::where('VAR_UID', $v1->VAR_UID)->first();

        $v2 = factory(ProcessVariable::class)->create($varsParams);
        $var2 = ProcessVariable::where('VAR_UID', $v2->VAR_UID)->first();

        $field1 = factory(ReportTableColumn::class)
            ->create([
                'report_table_id' => $newReport->id,
                'dynaform_id' => $var1->VAR_ID,
                'dynaform_name' => $var1->VAR_NAME,
                'process_variable_id' => $var1->VAR_ID
            ]);

        $field2 = factory(ReportTableColumn::class)
            ->create([
                'report_table_id' => $newReport->id,
                'dynaform_id' => $var2->VAR_ID,
                'dynaform_name' => $var2->VAR_NAME,
                'process_variable_id' => $var2->VAR_ID
            ]);

        $column1 = SchemaManager::setDefaultsForReportTablesFields($field1->toArray(), $var1);
        $column2 = SchemaManager::setDefaultsForReportTablesFields($field2->toArray(), $var2);

        $columnAppUid = [
            'additional_table_id' => $pmTable->id,
            'name' => 'APP_UID',
            'description' => 'String Field',
            'type' => 'VARCHAR',
            'size' => 36,
            'null' => 0,
            'connection' => factory(DbSource::class)->create()->uid
        ];

        SchemaManager::dropPhysicalTable($newReport->name);
        SchemaManager::updateOrCreateColumn($pmTable, $columnAppUid);
        SchemaManager::updateOrCreateColumn($pmTable, $column1);
        SchemaManager::updateOrCreateColumn($pmTable, $column2);

        return $newReport;
    }

    /**
     * Creates a report table and its physical tables/columns with its data filled.
     *
     * @return ReportTable
     */
    public function createAndPopulateTestReportTable(): ReportTable
    {
        // we create a report table
        $report = $this->createDefaultReportTable('string');

        // we add 2 variables to the rerport table
        $var1 = ProcessVariable::where('PRO_ID', $report->process_id)
            ->orderBy('VAR_ID', 'ASC')
            ->first();

        $var2 = ProcessVariable::where('PRO_ID', $report->process_id)
            ->orderBy('VAR_ID', 'DESC')
            ->first();

        // data that will be used for the instances that will be created
        $dataInstance1 = json_encode([$var1->VAR_NAME => 'Var1Instance1', $var2->VAR_NAME => 'Var2Instance1']);
        $dataInstance2 = json_encode([$var1->VAR_NAME => 'Var1Instance2', $var2->VAR_NAME => 'Var2Instance2']);

        // create 2 instances with the data that was initialized above
        factory(Application::class)
            ->create([
                'process_id' => $report->process->id,
                'APP_DATA' => $dataInstance1
            ]);

        factory(Application::class)
            ->create([
                'process_id' => $report->process->id,
                'APP_DATA' => $dataInstance2
            ]);

        return $report;
    }

    /**
     * Returns the data that will be used in the tests for the creation of a report table
     *
     * @param $process , process from which the report table will be created
     * @return array
     */
    private function newReportTableData($process): array
    {
        return [
            'process_uid' => $process->uid,
            'name' => 'ReportTableTest',
            'description' => 'Report table for testing purposes',
            'type' => 'NORMAL',
            'grid' => '',
            'connection' => factory(DbSource::class)->create()->uid,
            'fields' => [
                [
                    'dynaform_name' => $process->variables->first()->FLD_NAME,
                    'name' => 'NameForColumn',
                    'description' => 'MyTestField',
                    'type' => 'VARCHAR',
                    'size' => 32,
                ]
            ]
        ];
    }

    protected function tearDown(): void
    {
        if ($this->getName() === 'testGetAllData') {
            $this->artisan('migrate:fresh');
            $this->seed();
        }
    }
}