<?php

namespace Tests\Unit;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ProcessMaker\Model\ProcessCategory;
use Tests\TestCase;

class ProcessCategoryTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Tests model validatiions
     */
    public function testValidations()
    {
        $pc = new ProcessCategory();
        $this->assertFalse($pc->isValid());

        $pc->name = 'Test category';
        $pc->status = ProcessCategory::STATUS_ACTIVE;
        $pc->isValid();
        $this->assertTrue($pc->isValid());
        
        $pc->status = 'invalid';
        $this->assertFalse($pc->isValid());
    }

}
