<?php

namespace ProcessMaker\Models;

use ProcessMaker\Models\ScreenCategory;
use Tests\TestCase;

class ScreenCategoryTest extends TestCase
{
    /**
     * Test ScreenCategory Model.
     *
     * @return void
     */
    public function testGetNamesByIds()
    {
        //Case 1: one Id
        $stringIds = '1';
        $this->assertEquals(
            "Uncategorized",
            ScreenCategory::getNamesByIds($stringIds)
        );

        //Case 2: multiple Id
        $stringIds = '1,3';
        $this->assertEquals(
            "Uncategorized",
            ScreenCategory::getNamesByIds($stringIds)
        );

        //Case 3: without Id
        $stringIds = '';
        $this->assertEquals(
            "Uncategorized",
            ScreenCategory::getNamesByIds($stringIds)
        );

        //Case 4: non-existentId
        $stringIds = '9452';
        $this->assertEquals(
            "Uncategorized",
            ScreenCategory::getNamesByIds($stringIds)
        );
    }
}
