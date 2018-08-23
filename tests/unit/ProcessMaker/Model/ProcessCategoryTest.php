<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use ProcessMaker\Model\ProcessCategory;
use ProcessMaker\Model\Process;
use Illuminate\Validation\ValidationException;
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

    public function testHasProcessesValidation()
    {
        $process = factory(Process::class)->create();
        $processCategory = $process->category;

        $this->expectException(ValidationException::class);

        $processCategory->delete();
    }
}
