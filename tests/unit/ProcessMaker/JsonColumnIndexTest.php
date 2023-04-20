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

        // Make sure we're using the index
        // $result = DB::select("EXPLAIN SELECT * FROM {$this->table} WHERE (json_unquote(json_extract(`data`, '$.\"stringValue\"')) = 'foo')");
        // $result = DB::select("EXPLAIN SELECT * FROM {$this->table} WHERE data->>\"$.stringNumberValue\" < '100'");
        $result = ProcessRequest::pmql('data.numberValue = 123')->explain();
        // dd(ProcessRequest::pmql('data.numberValue = 123')->toSql());
        // $this->assertEquals('data_$.stringNumberValue', $result[0]->possible_keys);

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

        // dump(ProcessRequest::pmql('data.numberValue > 100')->getBindings());
        dump(ProcessRequest::pmql('data.numberValue > "100"')->toSql());
        dd('tmp');

        $this->assertEquals(1, ProcessRequest::pmql('data.stringValue = "foo"')->count());
        $this->assertEquals(1, ProcessRequest::pmql('data.numberValue = "99"')->count());
        $this->assertEquals(1, ProcessRequest::pmql('data.numberValue = 99')->count());
        $this->assertEquals(1, ProcessRequest::pmql('data.stringNumberValue = "42"')->count());
        $this->assertEquals(1, ProcessRequest::pmql('data.stringNumberValue = 42')->count());

        $this->assertEquals(2, ProcessRequest::pmql('data.numberValue < 100')->count());
        // $this->assertEquals(2, ProcessRequest::pmql('data.numberValue < "100"')->count());
        //dd(ProcessRequest::pmql('data.numberValue < "100"')->toSql());

        $this->assertEquals(1, ProcessRequest::pmql('data.numberValue > 50')->count());
        $this->assertEquals(2, ProcessRequest::pmql('data.stringNumberValue < 100')->count());
        // $this->assertEquals(2, ProcessRequest::pmql('data.stringNumberValue < "100"')->count());
        $this->assertEquals(1, ProcessRequest::pmql('data.stringNumberValue > 50')->count());

        $this->assertEquals(2, ProcessRequest::pmql('data.stringNumberValue in [42,99]')->count());
        $this->assertEquals(2, ProcessRequest::pmql('data.stringNumberValue in ["42","99"]')->count());
        $this->assertEquals(2, ProcessRequest::pmql('data.numberValue in [42,99]')->count());
        $this->assertEquals(2, ProcessRequest::pmql('data.numberValue in ["42","99"]')->count());

        // Cleanup
        DB::rollback();
        foreach ($fields as $field) {
            JsonColumnIndex::remove($this->table, 'data', '$.' . $field);
        }
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
