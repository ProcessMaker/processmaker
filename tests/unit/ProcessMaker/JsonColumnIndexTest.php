<?php

namespace Tests;

use DB;
use Facades\ProcessMaker\JsonColumnIndex;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;

class JsonColumnIndexTest extends TestCase
{
    private $table = 'process_requests';

    protected function connectionsToTransact()
    {
        return [];
    }

    public function test()
    {
        $fields = ['stringValue', 'numberValue', 'stringNumberValue'];

        foreach ($fields as $field) {
            JsonColumnIndex::add($this->table, 'data', '$.' . $field);
        }

        $indexes = collect(DB::select("SHOW INDEXES FROM {$this->table}"));

        foreach ($fields as $field) {
            $this->assertTrue($indexes->contains(fn ($i) => $i->Key_name === 'data_$.' . $field));
        }

        // Attempt to add it again
        $originalCount = $indexes->count();
        JsonColumnIndex::add($this->table, 'data', '$.stringValue');
        $indexes = collect(DB::select("SHOW INDEXES FROM {$this->table}"));
        $this->assertEquals($originalCount, $indexes->count());

        // Assert PMQL works
        DB::beginTransaction();

        $process = Process::factory()->create();
        $pr1 = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'data' => ['stringValue' => 'foo', 'numberValue' => 99, 'stringNumberValue' => '99'],
        ]);
        $pr2 = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'data' => ['stringValue' => 'bar', 'numberValue' => 42, 'stringNumberValue' => '42'],
        ]);

        $this->assertPMQLResult(1, 'data.stringValue = "foo"', 'data_$.stringValue');
        $this->assertPMQLResult(1, 'data.numberValue = "99"', 'data_$.numberValue');
        $this->assertPMQLResult(1, 'data.numberValue = 99', null); // does not use index on integers
        $this->assertPMQLResult(1, 'data.stringNumberValue = "42"', 'data_$.stringNumberValue');
        $this->assertPMQLResult(1, 'data.stringNumberValue = 42', null); // does not use index on integers
        $this->assertPMQLResult(2, 'data.stringNumberValue in ["42","99"]', 'data_$.stringNumberValue');

        // Cleanup
        DB::rollback();
        foreach ($fields as $field) {
            JsonColumnIndex::remove($this->table, 'data', '$.' . $field);
        }
    }

    private function assertPMQLResult($expected, $query, $index = null)
    {
        if ($index) {
            $explain = ProcessRequest::pmql($query)->explain();
            $this->assertEquals($index, $explain[0]->key);
        }
        $this->assertEquals($expected, ProcessRequest::pmql($query)->count());
    }

    public function testCustomMysqlGrammar()
    {
        $processRequest = ProcessRequest::pmql('data.firstname = "Agustin"')->toSql();
        $this->assertEquals(
            'select * from `process_requests` where (data->>"$.firstname" = ?)',
            $processRequest
        );
    }
}
