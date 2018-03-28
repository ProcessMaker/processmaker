<?php

namespace Tests\Unit;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Facades\ReportTableManager;
use ProcessMaker\Facades\SchemaManager;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\ProcessVariable;
use ProcessMaker\Model\ReportTable;
use ProcessMaker\Model\ReportTableVariable;
use Tests\TestCase;

/**
 * Class that tests the ReportTable model
 */
class ReportTableTest extends TestCase
{
    /**
     * Tests that the model returns all the data of the report table
     */
    public function testAllDataRows()
    {
        // we empty the list of instances
        DB::table('APPLICATION')->delete();

        $report = $this->createAndPopulateTestReportTable();
        ReportTableManager::populateFromInstanceVariables($report);
        $allData = $report->allDataRows();
        $this->assertCount(2, $allData);
    }

    /**
     * Tests that the pmTable related to the report table is returned
     */
    public function testGetAssociatedPmTable()
    {
        $report = $this->createDefaultReportTable();
        $pmTable = $report->getAssociatedPmTable();
        $this->assertEquals($report->ADD_TAB_UID, $pmTable->ADD_TAB_UID);
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
}