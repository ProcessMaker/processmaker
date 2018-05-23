<?php

namespace Tests\Unit;

use Ramsey\Uuid\Uuid;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Facades\SchemaManager;
use ProcessMaker\Model\PmTable;
use Tests\TestCase;

class SchemaManagerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Tests the addition or update of a column in a physical table
     */
    public function testUpdateOrCreateColumn()
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        SchemaManager::dropPhysicalTable('PMT_TESTPMTABLE');

        $pmTable = factory(PmTable::class)->create();

        $field1 = [
            'additional_table_id' => $pmTable->id,
            'name' => 'StringField',
            'description' => 'String Field',
            'type' => 'VARCHAR',
            'size' => 100,
            'null' => 1
        ];

        $field2 = [
            'additional_table_id' => $pmTable->id,
            'name' => 'IntegerField',
            'description' => 'Integer Field',
            'type' => 'INTEGER',
            'key' => 1
        ];

        SchemaManager::updateOrCreateColumn($pmTable, $field1);
        SchemaManager::updateOrCreateColumn($pmTable, $field2);
        $metadata = $pmTable->getTableMetadata();
        $this->assertCount(2, $metadata->columns, 'The PmTable should have two columns');


        // now we change the column size
        $field1['type'] = 'VARCHAR';
        $field1['size'] = 500;
        SchemaManager::updateOrCreateColumn($pmTable, $field1);
        $metadata = $pmTable->getTableMetadata();
        $this->assertEquals($metadata->columns[0]->size, 500, 'Now the first column is a varchar of 500 characters');

        // now we change the column type
        $field1['type'] = 'MEDIUMTEXT';
        SchemaManager::updateOrCreateColumn($pmTable, $field1);
        $metadata = $pmTable->getTableMetadata();
        $this->assertEquals($metadata->columns[0]->type, 'text', 'The first column now must be returned as text');


        // now we add a new column and change the key to the new table
        $field3 = [
            'additional_table_id' => $pmTable->id,
            'name' => 'IntegerField2',
            'description' => 'Integer Field2',
            'type' => 'INTEGER',
            'key' => 1
        ];

        SchemaManager::updateOrCreateColumn($pmTable, $field3);
        $metadata = $pmTable->getTableMetadata();
        $this->assertCount(1, $metadata->primaryKeyColumns, 'Just one column is primary key in the test table');
        $this->assertArraySubset([$field3['name']], $metadata->primaryKeyColumns,
            'Now the key is the new added column:'. $field3['name']);
    }

    /**
     * Tests that the columns' metadata is filled correctly from the schema
     */
    public function testGetColumnsFromSchema()
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        SchemaManager::dropPhysicalTable('PMT_TESTPMTABLE');

        $pmTable = factory(PmTable::class)->create();

        $field1 = [
            'additional_table_id' => $pmTable->id,
            'name' => 'IntegerField',
            'description' => 'Integer Field',
            'type' => 'INTEGER',
            'null' => 1
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
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        SchemaManager::dropPhysicalTable('PMT_TESTPMTABLE');

        $pmTable = factory(PmTable::class)->create();

        $field1 = [
            'additional_table_id' => $pmTable->id,
            'name' => 'StringField',
            'description' => 'String Field',
            'type' => 'VARCHAR',
            'size' => 250,
            'null' => 1
        ];

        $field2 = [
            'additional_table_id' => $pmTable->id,
            'name' => 'StringField2',
            'description' => 'String Field 2',
            'type' => 'VARCHAR',
            'size' => 250,
            'null' => 1
        ];

        SchemaManager::updateOrCreateColumn($pmTable, $field1);
        SchemaManager::updateOrCreateColumn($pmTable, $field2);

        SchemaManager::dropColumn($pmTable, $field1['name']);
        $columns = SchemaManager::getMetadataFromSchema($pmTable)->columns;
        $this->assertCount(1, $columns, 'After the column delete, on column must remain');
    }

    /**
     * Tests that a physical table is removed
     */
    public function testDropTable()
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        $pmTable = factory(PmTable::class)->create();

        $field1 = [
            'additional_table_id' => $pmTable->id,
            'name' => 'StringField',
            'description' => 'String Field',
            'type' => 'VARCHAR',
            'size' => 250,
            'null' => 1
        ];

        SchemaManager::updateOrCreateColumn($pmTable, $field1);
        SchemaManager::dropPhysicalTable($pmTable->physicalTableName());

        $this->assertNotTrue(Schema::hasTable($pmTable->physicalTableName()));
    }
}