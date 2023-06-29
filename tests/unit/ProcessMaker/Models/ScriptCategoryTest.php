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

        $category = ScriptCategory::factory()->create([
            'name' => 'Screen Category 19',
        ]);
        $this->assertEquals(
            $category->name,
            ScriptCategory::getNamesByIds($category->id)
        );

        //Case 2: without Id
        $stringIds = '';
        $this->assertEquals(
            "",
            ScriptCategory::getNamesByIds($stringIds)
        );

        //Case 3: non-existentId
        $stringIds = '9452';
        $this->assertEquals(
            "",
            ScriptCategory::getNamesByIds($stringIds)
        );
    }
}
