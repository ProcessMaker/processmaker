<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Facades\SchemaManager;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\ProcessVariable;
use ProcessMaker\Model\ReportTable;
use ProcessMaker\Model\ReportTableVariable;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;
use Ramsey\Uuid\Uuid;

class ReportTableControllerTest extends ApiTestCase
{
    /**
     * Test to retrieve all report tables of a process
     */
    public function testGetAllReportTables()
    {
        $report = $this->createDefaultReportTable();
        $url = "/api/1.0/project/" . $report->PRO_UID . "/report-tables";

        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $response->assertJsonStructure(
            [['rep_tab_name', 'rep_tab_grid', 'rep_tab_connection', 'fields']]
        );

        $returnedList = json_decode($response->getContent());
        $this->assertGreaterThanOrEqual(1, count($returnedList),
            'At least one additional ReportTable should exist');
        $this->assertGreaterThanOrEqual(1, count($returnedList[0]->fields),
            'The returned test ReportTable must have fields ');
    }

    /**
     * Test to retrieve on report table
     */
    public function testGetOneReportTable()
    {
        $report = $this->createDefaultReportTable();
        $url = "/api/1.0/project/" . $report->PRO_UID . "/report-table/" . $report->ADD_TAB_UID;

        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $response->assertJsonStructure(['rep_tab_name', 'rep_tab_grid', 'rep_tab_connection', 'fields']);
    }

    /**
     * Tests the addition of a report table
     */
    public function testCreateOneReportTable()
    {
        $report = $this->createDefaultReportTable();
        $process = $report->process;
        $reportJsonApi = $this->newReportTableData($report->process);

        $url = '/api/1.0/project/' . $process->PRO_UID . '/report-table';
        $response = $this->api('POST', $url, $reportJsonApi);
        $response->assertStatus(201);
        $returnedObject = json_decode($response->getContent());
        $response->assertJsonStructure([
            'rep_tab_name',
            'rep_tab_grid',
            'rep_tab_connection',
            'fields' => ['*' => ['fld_name', 'fld_type']]
        ]);

        $this->assertTrue($returnedObject->rep_tab_name === $reportJsonApi['rep_tab_name'],
            'The added ReportTable has not the passed name');

        $this->assertGreaterThanOrEqual(0, count($returnedObject->fields),
            'The added ReportTable must have fields');
    }

    /**
     * Test to update a report table
     */
    public function testUpdateOneReportTable()
    {
        $report = $this->createDefaultReportTable();
        $numberOfColumnsBeforeUpdate = count($report->fields);
        $description = 'Changed Description';

        // we update the pmTable
        $url = "/api/1.0/project/" . $report->PRO_UID . "/report-table/" . $report->ADD_TAB_UID;
        $response = $this->api('PUT', $url, [
            'rep_tab_description' => $description,
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
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'rep_tab_name',
            'rep_tab_grid',
            'rep_tab_connection',
            'fields' => ['*' => ['fld_name', 'fld_type']]
        ]);

        $returnedObject = json_decode($response->getContent());
        $this->assertEquals($returnedObject->rep_tab_description, $description);
        $this->assertEquals($numberOfColumnsBeforeUpdate + 1, count($returnedObject->fields),
            'The updated report table should have 1 additional field');
    }

    /**
     * Test to delete a report table
     */
    public function testDeleteReportTable()
    {
        $report = $this->createDefaultReportTable();
        $numSourcesBefore = ReportTable::count();

        $url = "/api/1.0/project/" . $report->PRO_UID . "/report-table/" . $report->ADD_TAB_UID;
        $response = $this->api('DELETE', $url);
        $numSourcesAfter = ReportTable::count();
        $response->assertStatus(204);
        $this->assertEquals($numSourcesBefore, $numSourcesAfter + 1);
    }

    /**
     * Test to populate a report table with the data of the instances of a process
     */
    public function testPopulateDataReport()
    {
        // we empty the list of instances
        DB::table('APPLICATION')->delete();

        $report = $this->createAndPopulateTestReportTable();
        $pmTable = $report->getAssociatedPmTable();

        // call to populate report table
        $url = "/api/1.0/project/" . $report->PRO_UID . "/report-table/" . $report->ADD_TAB_UID . "/populate";
        $response = $this->api('GET', $url);
        $response->assertStatus(200);

        $repTableCount = DB::table($pmTable->physicalTableName())->count();
        $this->assertEquals(2, $repTableCount, 'the variables of 2 instances must be in the report table');

        // calling again the endpoint shoud not add new rows, but delete the report table and fill it again
        $response = $this->api('GET', $url);
        $response->assertStatus(200);

        $repTableCount = DB::table($pmTable->physicalTableName())->count();
        $this->assertEquals(2, $repTableCount, 'the variables of 2 instances must be in the report table');
    }

    /**
     * Test to get all the data of a report table
     */
    public function testGetAllData()
    {
        $report = $this->createAndPopulateTestReportTable();
        $url = "/api/1.0/project/" . $report->PRO_UID . "/report-table/" . $report->ADD_TAB_UID . "/data";
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
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
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);

        $this->auth($user->username, 'password');
    }

    /**
     * Creates a basic report table to be used in the tests
     *
     * @param string null $forceVariableTypesTo, name of the variable type that all the columns of the
     *  report table will have, it it is not set, a random type will be used
     * @return mixed
     */
    private function createDefaultReportTable($forceVariableTypesTo = null)
    {
        SchemaManager::dropPhysicalTable('PMT_REPORT_TEST');

        // we create a report table
        $report = factory(ReportTable::class)->create();
        $newReport = ReportTable::whereAddTabUid($report->ADD_TAB_UID)->first();
        $pmTable = $report->getAssociatedPmTable();

        // we add some variables to the report table
        $varsParams = [];
        $varsParams ['PRO_ID'] = $newReport->process->PRO_ID;
        if ($forceVariableTypesTo !== null) {
            $varsParams['VAR_FIELD_TYPE'] = $forceVariableTypesTo;
        }

        $v1 = factory(ProcessVariable::class)->create($varsParams);
        $var1 = ProcessVariable::where('VAR_UID', $v1->VAR_UID)->first();

        $v2 = factory(ProcessVariable::class)->create($varsParams);
        $var2 = ProcessVariable::where('VAR_UID', $v2->VAR_UID)->first();

        $field1 = factory(ReportTableVariable::class)
            ->create([
                'ADD_TAB_UID' => $newReport->ADD_TAB_UID,
                'ADD_TAB_ID' => $newReport->ADD_TAB_ID,
                'FLD_DYN_UID' => $var1->VAR_UID,
                'FLD_DYN_NAME' => $var1->VAR_NAME,
                'VAR_ID' => $var1->VAR_ID
            ]);

        $field2 = factory(ReportTableVariable::class)
            ->create([
                'ADD_TAB_UID' => $newReport->ADD_TAB_UID,
                'ADD_TAB_ID' => $newReport->ADD_TAB_ID,
                'FLD_DYN_UID' => $var2->VAR_UID,
                'FLD_DYN_NAME' => $var2->VAR_NAME,
                'VAR_ID' => $var2->VAR_ID
            ]);

        $column1 = SchemaManager::setDefaultsForReportTablesFields($field1->toArray(), $var1);
        $column2 = SchemaManager::setDefaultsForReportTablesFields($field2->toArray(), $var2);

        $columnAppUid = [
            'FLD_UID' => str_replace('-', '', Uuid::uuid4()),
            'ADD_TAB_UID' => $pmTable->ADD_TAB_UID,
            'FLD_NAME' => 'APP_UID',
            'FLD_DESCRIPTION' => 'String Field',
            'FLD_TYPE' => 'VARCHAR',
            'FLD_SIZE' => 32,
            'FLD_NULL' => 0
        ];

        SchemaManager::dropPhysicalTable($newReport->ADD_TAB_NAME);
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
    public function createAndPopulateTestReportTable()
    {
        // we create a report table
        $report = $this->createDefaultReportTable('string');

        // we add 2 variables to the rerport table
        $var1 = ProcessVariable::where('PRO_ID', $report->PRO_ID)
                ->orderBy('VAR_ID', 'ASC')
                ->first();

        $var2 = ProcessVariable::where('PRO_ID', $report->PRO_ID)
                ->orderBy('VAR_ID', 'DESC')
                ->first();

        // data that will be used for the instances that will be created
        $dataInstance1 = json_encode([$var1->VAR_NAME => 'Var1Instance1', $var2->VAR_NAME => 'Var2Instance1']);
        $dataInstance2 = json_encode([$var1->VAR_NAME => 'Var1Instance2', $var2->VAR_NAME => 'Var2Instance2']);

        // create 2 instances with the data that was initialized above
        factory(Application::class)
            ->create([
                'PRO_UID' => $report->PRO_UID,
                'APP_DATA' => $dataInstance1
            ]);

        factory(Application::class)
            ->create([
                'PRO_UID' => $report->PRO_UID,
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
    private function newReportTableData($process)
    {
        return [
            'pro_uid' => $process->PRO_UID,
            'rep_tab_name' => 'ReportTableTest',
            'rep_tab_dsc' => 'Report table for testing purposes',
            'rep_tab_connection' => env('DB_DATABASE'),
            'rep_tab_type' => 'NORMAL',
            'rep_tab_grid' => '',
            'fields' => [
                [
                    "fld_dyn" => $process->variables->first()->FLD_NAME,
                    "fld_name" => "NameForColumn",
                    "fld_label" => "MyTestField",
                    "fld_type" => "VARCHAR",
                    "fld_size" => 32
                ]
            ]
        ];
    }
}