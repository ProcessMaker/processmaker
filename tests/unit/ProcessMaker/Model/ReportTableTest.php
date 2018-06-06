<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Facades\ReportTableManager;
use ProcessMaker\Facades\SchemaManager;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\ProcessVariable;
use ProcessMaker\Model\ReportTable;
use ProcessMaker\Model\ReportTableColumn;
use Tests\TestCase;

/**
 * Class that tests the ReportTable model
 */
class ReportTableTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * Tests that the model returns all the data of the report table
     */
    public function testAllDataRows(): void
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
    public function testGetAssociatedPmTable(): void
    {
        $report = $this->createDefaultReportTable();
        $pmTable = $report->getAssociatedPmTable();
        $this->assertEquals($report->uid, $pmTable->uid);
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
            'type' => 'char',
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

    protected function tearDown(): void
    {
        if ($this->getName() === 'testGetAssociatedPmTable') {
            $this->artisan('migrate:fresh');
            $this->seed();
        }
    }
}