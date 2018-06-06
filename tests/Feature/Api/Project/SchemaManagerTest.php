<?php

namespace Tests\Unit;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Facades\SchemaManager;
use ProcessMaker\Model\PmTable;
use Tests\TestCase;

class SchemaManagerTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * Create PM table with columns
     */
    public function testCreateColumns(): void
    {
        $faker = Faker::create();
        $pmTable = factory(PmTable::class)->create();

        $numberFields = $faker->randomDigitNotNull;
        $this->addColumns($pmTable, $numberFields);
        $this->assertCount($numberFields, $pmTable->getTableMetadata()->columns, 'The Pmtable did not add all the columns');
    }

    /**
     * Create PM table with column auto increment
     */
    public function testCreateColumnAutoIncrement(): void
    {
        $faker = Faker::create();
        $pmTable = factory(PmTable::class)->create();

        $numberFields = $faker->randomDigitNotNull;
        $this->addColumns($pmTable, $numberFields);

        SchemaManager::updateOrCreateColumn($pmTable, [
            'additional_table_id' => $pmTable->id,
            'name' => 'field_' . $numberFields++,
            'description' => $faker->sentence(4),
            'type' => 'INTEGER',
            'key' => 1,
            'auto_increment' => 0
        ]);

        SchemaManager::updateOrCreateColumn($pmTable, [
            'additional_table_id' => $pmTable->id,
            'name' => 'field_' . $numberFields++,
            'description' => $faker->sentence(4),
            'type' => 'INTEGER',
            'key' => 1,
            'auto_increment' => 1
        ]);

        $this->assertCount($numberFields, $pmTable->getTableMetadata()->columns, 'The Pmtable did not add all the columns');
    }

    /**
     * Update PM table with column
     */
    public function testUpdateColumns(): void
    {
        $faker = Faker::create();
        $pmTable = factory(PmTable::class)->create();

        $numberFields = $faker->randomDigitNotNull;
        $this->addColumns($pmTable, $numberFields);

        $numberRandom = $faker->numberBetween(0, $numberFields - 1);
        $field = (array)$pmTable->getTableMetadata()->columns[$numberRandom];
        $field['type'] = 'VARCHAR';
        $field['size'] = $faker->numberBetween(100, 500);

        SchemaManager::updateOrCreateColumn($pmTable, $field);
        $metadata = $pmTable->getTableMetadata();
        $this->assertEquals($metadata->columns[$numberRandom]->type, 'string', 'The type of column is not the same');
        $this->assertEquals($metadata->columns[$numberRandom]->size, $field['size'], 'The size of column is not the same');
    }

    /**
     * Tests that the columns' metadata is filled correctly from the schema
     */
    public function testGetColumnsFromSchema(): void
    {
        $faker = Faker::create();
        $pmTable = factory(PmTable::class)->create();

        $numberFields = $faker->randomDigitNotNull;
        $this->addColumns($pmTable, $numberFields);
        $this->assertCount($numberFields, $pmTable->getTableMetadata()->columns, "The PmTable should have $numberFields columns");
    }

    /**
     * Tests that a column is removed from the physical table
     */
    public function testDropColumns()
    {
        $faker = Faker::create();
        $pmTable = factory(PmTable::class)->create();

        $numberFields = $faker->randomDigitNotNull;
        $this->addColumns($pmTable, $numberFields);

        $numberRandom = $faker->numberBetween(0, $numberFields - 1);
        $field = (array)$pmTable->getTableMetadata()->columns[$numberRandom];

        SchemaManager::dropColumn($pmTable, $field['name']);
        $metadata = $pmTable->getTableMetadata();
        $this->assertCount($numberFields - 1, $metadata->columns, 'The column was not deleted');

    }

    /**
     * Tests that a physical table is removed
     */
    public function testDropTable()
    {
        $faker = Faker::create();
        $pmTable = factory(PmTable::class)->create();

        $numberFields = $faker->randomDigitNotNull;
        $this->addColumns($pmTable, $numberFields);

        SchemaManager::dropPhysicalTable($pmTable->physicalTableName());
        $this->assertNotTrue(Schema::hasTable($pmTable->physicalTableName()));
    }

    /**
     * Add columns to PmTable
     *
     * @param PmTable $pmTable
     * @param integer $numberColumns
     */
    private function addColumns(PmTable $pmTable, $numberColumns): void
    {
        $faker = Faker::create();
        for ($i = 0; $i < $numberColumns; $i++) {
            $type = $faker->randomElement(['INTEGER', 'VARCHAR', 'MEDIUMTEXT']);
            SchemaManager::updateOrCreateColumn($pmTable, [
                'additional_table_id' => $pmTable->id,
                'name' => 'field_' . $i,
                'description' => $faker->sentence(4),
                'type' => $type,
                'size' => 100,
            ]);
        }
    }

    protected function tearDown(): void
    {
        if ($this->getName() === 'testDropTable') {
            $this->artisan('migrate:fresh');
            $this->seed();
        }
    }

}