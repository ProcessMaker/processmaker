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
        $category = ScreenCategory::factory()->create([
            'name' => 'Screen Category 1',
        ]);
        $this->assertEquals(
            $category->name,
            ScreenCategory::getNamesByIds($category->id)
        );

        //Case 2: more than one Id
        $category2 = ScreenCategory::factory()->create([
            'name' => 'Screen Category 33',
        ]);
        $this->assertEquals(
            $category->name . ', '. $category2->name,
            ScreenCategory::getNamesByIds($category->id . ',' . $category2->id)
        );

        //Case 3: without Id
        $stringIds = '';
        $this->assertEquals(
            "",
            ScreenCategory::getNamesByIds($stringIds)
        );

        //Case 4: non-existentId
        $stringIds = '9452';
        $this->assertEquals(
            "",
            ScreenCategory::getNamesByIds($stringIds)
        );
    }
}
