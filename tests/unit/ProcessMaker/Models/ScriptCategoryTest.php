<?php

namespace ProcessMaker\Models;

use ProcessMaker\Models\ScriptCategory;
use Tests\TestCase;

class ScriptCategoryTest extends TestCase
{
    /**
     * Test ScriptCategory Model.
     *
     * @return void
     */
    public function testGetNamesByIds()
    {
        //Case 1: one Id
        $category = ScriptCategory::factory()->create([
            'name' => 'Screen Category 13',
        ]);
        $this->assertEquals(
            $category->name,
            ScriptCategory::getNamesByIds($category->id)
        );

        //Case 2: more than one Id
        $category2 = ScriptCategory::factory()->create([
            'name' => 'Screen Category 33',
        ]);
        $this->assertEquals(
            $category->name. ', '.$category2->name,
            ScriptCategory::getNamesByIds($category->id. ',' . $category2->id)
        );

        //Case 3: without Id
        $stringIds = '';
        $this->assertEquals(
            "",
            ScriptCategory::getNamesByIds($stringIds)
        );

        //Case 4: non-existentId
        $stringIds = '9452';
        $this->assertEquals(
            "",
            ScriptCategory::getNamesByIds($stringIds)
        );
    }
}
