<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Facades\SchemaManager;
use ProcessMaker\Model\PmTable;
use Tests\TestCase;

class PmTableTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @var PmTable $pmTable
     */
    protected $pmTable;

    /**
     * Overwrite of the setup method that authenticates and fills the default connection data
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->pmTable = $this->createTestPmTable();
    }

    /**
     * Tests the addition of a data row in the physical table
     */
    public function testAddDataRow(): void
    {
        $dataRow = ['StringField' => 'string field'];
        $response = $this->pmTable->addDataRow($dataRow);
        $this->assertTrue($response);
    }

    /**
     * Tests to get all the data rows from the physical table
     */
    public function testGetAllData(): void
    {
        $dataRow = ['StringField' => 'string field',];
        $this->pmTable->addDataRow($dataRow);
        $allData = $this->pmTable->allDataRows();
        $this->assertGreaterThanOrEqual(0, count($allData));
    }

    /**
     * Tests the update of a data row in the physical table
     */
    public function testUpdateDataRow(): void
    {
        $dataRow = ['StringField' => 'string field',];
        $this->pmTable->addDataRow($dataRow);

        $insertedRow = (array)DB::table($this->pmTable->physicalTableName())
            ->orderBy('IntegerField', 'desc')
            ->first();

        $updateString = 'Changed field.';
        $newData = [
            'IntegerField' => $insertedRow['IntegerField'],
            'StringField' => $updateString
        ];
        $updatedRow = $this->pmTable->updateDataRow($newData);
        $this->assertEquals($updatedRow['StringField'], $updateString);
    }

    /**
     * Tests that the when updating a row in the physical table
     * an exception is thrown if the table has not a primary key
     */
    public function testUpdateRowToTableWithoutKey(): void
    {
        SchemaManager::dropColumn($this->pmTable, 'IntegerField');

        $dataRow = ['StringField' => 'string field',];
        $this->expectException(\PDOException::class);
        $this->pmTable->updateDataRow($dataRow);
    }

    /**
     * Tests the deletion of a row in the in the physical table
     */
    public function testDeleteDataRow(): void
    {
        $dataRow = ['StringField' => 'string field',];
        $this->pmTable->addDataRow($dataRow);

        $insertedRow = (array)DB::table($this->pmTable->physicalTableName())
            ->orderBy('IntegerField', 'desc')
            ->first();

        $this->assertArrayHasKey('IntegerField', $insertedRow, 'A new dataRow should have been inserted.');

        $numberRowsBefore = count($this->pmTable->allDataRows());
        $this->pmTable->deleteDataRow(['IntegerField' => $insertedRow['IntegerField']]);
        $numberRowsAfter = count($this->pmTable->allDataRows());
        $this->assertEquals($numberRowsBefore - 1, $numberRowsAfter, 'After the deletion the PmTable must have on less dataRow.');
    }

    /**
     * Tests that the physical table name is set correctly
     */
    public function testPhysicalTableName(): void
    {
        $this->assertStringStartsWith('PMT_', $this->pmTable->physicalTableName(), 'The PmTable should begin with PMT_');
    }

    /**
     * Tests that the metadata of the table is filled correctly
     */
    public function testGetTableMetadata(): void
    {
        $metadata = $this->pmTable->getTableMetadata();
        $this->assertGreaterThanOrEqual(0, count($metadata->columns));
    }

    /**
     * Creates the PmTable used in the tests
     *
     * @return PmTable
     */
    private function createTestPmTable(): PmTable
    {
        // we create a new pmTable
        $pmTable = factory(PmTable::class)
            ->create();

        SchemaManager::updateOrCreateColumn($pmTable, [
            'additional_table_id' => $pmTable->id,
            'name' => 'StringField',
            'description' => 'String Field',
            'type' => 'VARCHAR',
            'size' => 250,
            'null' => 1
        ]);
        SchemaManager::updateOrCreateColumn($pmTable, [
            'additional_table_id' => $pmTable->id,
            'name' => 'IntegerField',
            'description' => 'Integer Field',
            'type' => 'INTEGER',
            'null' => 0,
            'key' => 1,
            'auto_increment' => 1
        ]);
        SchemaManager::updateOrCreateColumn($pmTable, [
            'additional_table_id' => $pmTable->id,
            'name' => 'TextField',
            'description' => 'Text Field',
            'type' => 'TEXT',
            'null' => 1
        ]);

        return $pmTable;
    }

    protected function tearDown(): void
    {
        if ($this->getName() === 'testGetTableMetadata') {
            $this->artisan('migrate:fresh');
            $this->seed();
        }
    }
}
