<?php

namespace ProcessMaker\Models;

use ProcessMaker\Models\ProcessCategory;
use Tests\TestCase;

class ProcessCategoryTest extends TestCase
{
    /**
     * Test ScriptCategory Model.
     *
     * @return void
     */
    public function testGetNamesByIds()
    {
        //Case 1: string id
        $stringIds = '1';
        $this->assertEquals(
            "Uncategorized",
            ProcessCategory::getNamesByIds($stringIds)
        );

        //Case 2: multiple Id
        $stringIds = '1,3';
        $this->assertEquals(
            "Uncategorized",
            ProcessCategory::getNamesByIds($stringIds)
        );

        //Case 3: without Id
        $stringIds = '';
        $this->assertEquals(
            "Uncategorized",
            ProcessCategory::getNamesByIds($stringIds)
        );

        //Case 4: non-existentId
        $stringIds = '9452';
        $this->assertEquals(
            "Uncategorized",
            ProcessCategory::getNamesByIds($stringIds)
        );
    }
}
