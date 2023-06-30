<?php

namespace ProcessMaker\Models;

use ProcessMaker\Models\ProcessCategory;
use Tests\TestCase;

class ProcessCategoryTest extends TestCase
{
    /**
     * Test ProcessCategory Model.
     *
     * @return void
     */
    public function testGetNamesByIds()
    {
        //Case 1: one Id
        $category = ProcessCategory::factory()->create([
            'name' => 'Screen Category 7',
        ]);
        $this->assertEquals(
            $category->name,
            ProcessCategory::getNamesByIds($category->id)
        );

        //Case 2: more than one Id
        $category2 = ProcessCategory::factory()->create([
            'name' => 'Screen Category 33',
        ]);
        $this->assertEquals(
            $category->name . ', '. $category2->name,
            ProcessCategory::getNamesByIds($category->id . ',' . $category2->id)
        );

        //Case 3: without Id
        $stringIds = '';
        $this->assertEquals(
            "",
            ProcessCategory::getNamesByIds($stringIds)
        );

        //Case 4: non-existentId
        $stringIds = '9452';
        $this->assertEquals(
            "",
            ProcessCategory::getNamesByIds($stringIds)
        );
    }
}
