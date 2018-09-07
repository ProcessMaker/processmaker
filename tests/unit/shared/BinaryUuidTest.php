<?php
namespace Tests\Unit\Shared;

use Illuminate\Foundation\Testing\DatabaseTransactions;

trait BinaryUuidTest
{
    use DatabaseTransactions;

    /**
     * Test a model that uses a binary uuid primary key.
     * It should get automatically generated and saved to the db.
     */
    public function testUuid()
    {
        $task = factory($this->class)->create();
        $uuid = $task->toArray()['uuid'];
        $this->assertRegExp('/^([a-zA-Z0-9]+-){4}[a-zA-Z0-9]+$/', $uuid);
    }
}