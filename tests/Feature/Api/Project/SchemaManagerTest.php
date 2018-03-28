<?php

namespace Tests\Unit;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Facades\SchemaManager;
use ProcessMaker\Model\PmTable;
use Tests\TestCase;

class SchemaManagerTest extends TestCase
{
    /**
     * Tests the addition or update of a column in a physical table
     */
    public function testUpdateOrCreateColumn()
    {
        SchemaManager::dropPhysicalTable('PMT_TESTPMTABLE');

        $factoryTable = factory(PmTable::class)->create();

        $pmTable = PmTable::where('ADD_TAB_UID', $factoryTable->ADD_TAB_UID)->get()[0];

        $field1 = [
            'FLD_UID' => str_replace('-', '', Uuid::uuid4()),
            'ADD_TAB_UID' => $pmTable->ADD_TAB_UID,
            'FLD_NAME' => 'StringField',
            'FLD_DESCRIPTION' => 'String Field',
            'FLD_TYPE' => 'VARCHAR',
            'FLD_SIZE' => 100,
            'FLD_NULL' => 1
        ];

        $field2 = [
            'FLD_UID' => str_replace('-', '', Uuid::uuid4()),
            'ADD_TAB_UID' => $pmTable->ADD_TAB_UID,
            'FLD_NAME' => 'IntegerField',
            'FLD_DESCRIPTION' => 'Integer Field',
            'FLD_TYPE' => 'INTEGER',
            'FLD_KEY' => 1
        ];

        SchemaManager::updateOrCreateColumn($pmTable, $field1);
        SchemaManager::updateOrCreateColumn($pmTable, $field2);
        $metadata = $pmTable->getTableMetadata();
        $this->assertCount(2, $metadata->columns, 'The PmTable should have two columns');


        // now we change the column size
        $field1['FLD_TYPE'] = 'VARCHAR';
        $field1['FLD_SIZE'] = 500;
        SchemaManager::updateOrCreateColumn($pmTable, $field1);
        $metadata = $pmTable->getTableMetadata();
        $this->assertEquals($metadata->columns[0]->FLD_SIZE, 500, 'Now the first column is a varchar of 500 characters');

        // now we change the column type
        $field1['FLD_TYPE'] = 'MEDIUMTEXT';
        SchemaManager::updateOrCreateColumn($pmTable, $field1);
        $metadata = $pmTable->getTableMetadata();
        $this->assertEquals($metadata->columns[0]->FLD_TYPE, 'text', 'The first column now must be returned as text');


        // now we add a new column and change the key to the new table
        $field3 = [
            'FLD_UID' => str_replace('-', '', Uuid::uuid4()),
            'ADD_TAB_UID' => $pmTable->ADD_TAB_UID,
            'FLD_NAME' => 'IntegerField2',
            'FLD_DESCRIPTION' => 'Integer Field2',
            'FLD_TYPE' => 'INTEGER',
            'FLD_KEY' => 1
        ];

        SchemaManager::updateOrCreateColumn($pmTable, $field3);
        $metadata = $pmTable->getTableMetadata();
        $this->assertCount(1, $metadata->primaryKeyColumns, 'Just one column is primary key in the test table');
        $this->assertArraySubset([$field3['FLD_NAME']], $metadata->primaryKeyColumns,
            'Now the key is the new added column:'. $field3['FLD_NAME']);
    }

    /**
     * Tests that the columns' metadata is filled correctly from the schema
     */
    public function testGetColumnsFromSchema()
    {
        SchemaManager::dropPhysicalTable('PMT_TESTPMTABLE');

        $factoryTable = factory(PmTable::class)->create();

        $pmTable = PmTable::where('ADD_TAB_UID', $factoryTable->ADD_TAB_UID)->get()[0];

        $field1 = [
            'FLD_UID' => str_replace('-', '', Uuid::uuid4()),
            'ADD_TAB_UID' => $pmTable->ADD_TAB_UID,
            'FLD_NAME' => 'IntegerField',
            'FLD_DESCRIPTION' => 'Integer Field',
            'FLD_TYPE' => 'INTEGER',
            'FLD_NULL' => 1
        ];

        SchemaManager::updateOrCreateColumn($pmTable, $field1);
        $columns = SchemaManager::getMetadataFromSchema($pmTable, $field1)->columns;

        $this->assertCount(1, $columns, 'The PmTable should have one column');
    }

    /**
     * Tests that a column is removed from the physical table
     */
    public function testDropColumns()
    {
        SchemaManager::dropPhysicalTable('PMT_TESTPMTABLE');

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
            'FLD_NAME' => 'StringField2',
            'FLD_DESCRIPTION' => 'String Field 2',
            'FLD_TYPE' => 'VARCHAR',
            'FLD_SIZE' => 250,
            'FLD_NULL' => 1
        ];

        SchemaManager::updateOrCreateColumn($pmTable, $field1);
        SchemaManager::updateOrCreateColumn($pmTable, $field2);

        SchemaManager::dropColumn($pmTable, $field1['FLD_NAME']);
        $columns = SchemaManager::getMetadataFromSchema($pmTable)->columns;
        $this->assertCount(1, $columns, 'After the column delete, on column must remain');
    }

    /**
     * Tests that a physical table is removed
     */
    public function testDropTable()
    {

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

        SchemaManager::updateOrCreateColumn($pmTable, $field1);
        SchemaManager::dropPhysicalTable($pmTable->physicalTableName());

        $this->assertNotTrue(Schema::hasTable($pmTable->physicalTableName()));
    }
}