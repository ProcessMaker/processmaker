<?php

namespace Tests;

use DB;
use Facades\ProcessMaker\JsonColumnIndex;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\ProcessRequest;

class JsonColumnIndexTest extends TestCase
{
    private $table = 'test_json_column_indexes';

    protected function connectionsToTransact()
    {
        return [];
    }

    public function test()
    {
        Schema::dropIfExists($this->table);

        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->json('data');
        });

        DB::table($this->table)->insert([
            'data' => '{"foo":"bar","num":99}',
        ]);
        DB::table($this->table)->insert([
            'data' => '{"foo":"baz", "num":42}',
        ]);

        JsonColumnIndex::add($this->table, 'data', '$.foo');
        JsonColumnIndex::add($this->table, 'data', '$.num');

        $indexes = DB::select("SHOW INDEXES FROM {$this->table}");

        $this->assertEquals('data_$.foo', $indexes[1]->Key_name);
        $this->assertEquals('data_$.num', $indexes[2]->Key_name);

        // Attempt to add it again
        JsonColumnIndex::add($this->table, 'data', '$.foo');
        $indexes = DB::select("SHOW INDEXES FROM {$this->table}");
        $this->assertEquals(3, count($indexes));

        // Make sure we're using the index
        $result = DB::select("EXPLAIN SELECT * FROM {$this->table} WHERE data->>\"$.foo\" = 'baz';");
        $this->assertEquals('data_$.foo', $result[0]->possible_keys);

        $result = DB::select("EXPLAIN SELECT * FROM {$this->table} WHERE data->>\"$.num\" = 42;");
        $this->assertEquals('data_$.num', $result[0]->possible_keys);

        Schema::dropIfExists($this->table);
    }

    public function testCustomMysqlGrammar()
    {
        $processRequest = ProcessRequest::where('data->firstname', 'Agustin')->toSql();
        $this->assertEquals(
            "select * from `process_requests` where `data`->>\"$.\", '$.\"firstname\"' = ?", 
            $processRequest
        ); 

    }
}
