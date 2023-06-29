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

        $category = ScreenCategory::factory()->create([
            'name' => 'Screen Category 15',
        ]);
        $this->assertEquals(
            $category->name,
            ScreenCategory::getNamesByIds($category->id)
        );

        //Case 2: without Id
        $stringIds = '';
        $this->assertEquals(
            "",
            ScreenCategory::getNamesByIds($stringIds)
        );

        //Case 3: non-existentId
        $stringIds = '9452';
        $this->assertEquals(
            "",
            ScreenCategory::getNamesByIds($stringIds)
        );
    }
}
