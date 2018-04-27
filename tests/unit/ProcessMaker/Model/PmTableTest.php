<?php

namespace Tests\Unit;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Facades\SchemaManager;
use ProcessMaker\Model\PmTable;
use Tests\TestCase;

class PmTableTest extends TestCase
{
    /**
     * Tests the addition of a data row in the physical table
     */
    public function testAddDataRow()
    {
        $dataRow = ['StringField' => 'string field'];
        $pmTable = $this->createTestPmTable();
        $response = $pmTable->addDataRow($dataRow);
        $this->assertTrue($response);
    }

    /**
     * Tests to get all the data rows from the physical table
     */
    public function testGetAllData()
    {
        $dataRow = ['StringField' => 'string field',];
        $pmTable = $this->createTestPmTable();
        $pmTable->addDataRow($dataRow);
        $allData = $pmTable->allDataRows();
        $this->assertGreaterThanOrEqual(0, count($allData));
    }

    /**
     * Tests the update of a data row in the physical table
     */
    public function testUpdateDataRow()
    {
        $pmTable = $this->createTestPmTable();
        $dataRow = ['StringField' => 'string field',];
        $pmTable->addDataRow($dataRow);

        $insertedRow = (array)DB::table('PMT_TESTPMTABLE')
            ->orderBy('IntegerField', 'desc')
            ->first();

        $updateString = "Changed field.";
        $newData = [
            'IntegerField' => $insertedRow['IntegerField'],
            'StringField' => $updateString
        ];
        $updatedRow = $pmTable->updateDataRow($newData);
        $this->assertEquals($updatedRow['StringField'], $updateString);
    }

    /**
     * Tests that the when updating a row in the physical table
     * an exception is thrown if the table has not a primary key
     */
    public function testUpdateRowToTableWithoutKey()
    {
        $pmTable = $this->createTestPmTable();
        SchemaManager::dropColumn($pmTable, 'IntegerField');

        $dataRow = ['StringField' => 'string field',];
        $this->expectException(\PDOException::class);
        $pmTable->updateDataRow($dataRow);
    }

    /**
     * Tests the deletion of a row in the in the physical table
     */
    public function testDeleteDataRow()
    {
        $pmTable = $this->createTestPmTable();
        $dataRow = ['StringField' => 'string field',];
        $pmTable->addDataRow($dataRow);

        $insertedRow = (array)DB::table('PMT_TESTPMTABLE')
            ->orderBy('IntegerField', 'desc')
            ->first();

        $this->assertArrayHasKey('IntegerField', $insertedRow, 'A new dataRow should have been inserted.');

        $numberRowsBefore = count($pmTable->allDataRows());
        $pmTable->deleteDataRow(['IntegerField' => $insertedRow['IntegerField']]);
        $numberRowsAfter = count($pmTable->allDataRows());
        $this->assertEquals($numberRowsBefore - 1, $numberRowsAfter, "After the deletion the PmTable must have on less dataRow.");
    }

    /**
     * Tests that the physical table name is set correctly
     */
    public function testPhysicalTableName()
    {
        $pmTable = $this->createTestPmTable();
        $this->assertStringStartsWith("PMT_", $pmTable->physicalTableName(), 'The PmTable should begin with PMT_');
    }

    /**
     * Tests that the metadata of the table is filled correctly
     */
    public function testGetTableMetadata()
    {
        $pmTable = $this->createTestPmTable();
        $metadata = $pmTable->getTableMetadata();
        $this->assertGreaterThanOrEqual(0, count($metadata->columns));
    }

    /**
     * Creates the PmTable used in the tests
     *
     * @return mixed
     */
    private function createTestPmTable()
    {
        // we create a new pmTable
        $pmTable = factory(PmTable::class)
            ->create();

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