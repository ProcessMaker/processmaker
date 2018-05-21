<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Facades\SchemaManager;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\ProcessVariable;
use ProcessMaker\Model\ReportTable;
use ProcessMaker\Model\ReportTableColumn;
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
        $url = "/api/1.0/project/" . $report->process->uid . "/report-tables";

        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $response->assertJsonStructure(
            ['data' => [['name', 'grid', 'connection', 'fields']]]
        );

        $returnedList = json_decode($response->getContent());
        $this->assertGreaterThanOrEqual(1, count($returnedList->data),
            'At least one additional ReportTable should exist');
        $this->assertGreaterThanOrEqual(1, count($returnedList->data[0]->fields),
            'The returned test ReportTable must have fields ');
    }

    /**
     * Test to retrieve on report table
     */
    public function testGetOneReportTable()
    {
        $report = $this->createDefaultReportTable();
        $url = "/api/1.0/project/" . $report->process->uid . "/report-table/" . $report->uid;

        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $response->assertJsonStructure(['name', 'grid', 'connection', 'fields']);
    }

    /**
     * Tests the addition of a report table
     */
    public function testCreateOneReportTable()
    {
        $report = $this->createDefaultReportTable();
        $process = $report->process;
        $reportJsonApi = $this->newReportTableData($report->process);

        $url = '/api/1.0/project/' . $process->uid . '/report-table';
        $response = $this->api('POST', $url, $reportJsonApi);
        $response->assertStatus(201);
        $returnedObject = json_decode($response->getContent());
        $response->assertJsonStructure([
            'name',
            'connection',
            'grid',
            'fields' => ['*' => ['name', 'type']]
        ]);

        $this->assertTrue($returnedObject->name === $reportJsonApi['name'],
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
        $url = "/api/1.0/project/" . $report->process->uid . "/report-table/" . $report->uid;
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

        $url = "/api/1.0/project/" . $report->process->uid . "/report-table/" . $report->uid;
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
        $url = "/api/1.0/project/" . $report->process->uid . "/report-table/" . $report->uid . "/populate";
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
        $url = "/api/1.0/project/" . $report->process->uid . "/report-table/" . $report->uid . "/data";
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
            'null' => 0
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
    public function createAndPopulateTestReportTable()
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
    private function newReportTableData($process)
    {
        return [
            'process_uid' => $process->uid,
            'name' => 'ReportTableTest',
            'description' => 'Report table for testing purposes',
            'type' => 'NORMAL',
            'grid' => '',
            'fields' => [
                [
                    "dynaform_name" => $process->variables->first()->FLD_NAME,
                    "name" => "NameForColumn",
                    "description" => "MyTestField",
                    "type" => "VARCHAR",
                    "size" => 32
                ]
            ]
        ];
    }
}