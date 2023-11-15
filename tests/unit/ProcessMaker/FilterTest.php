<?php

namespace Tests;

use DB;
use Facades\ProcessMaker\JsonColumnIndex;
use ProcessMaker\Models\ProcessRequest;

class FilterTest extends TestCase
{
    public function test()
    {
        $filterDefinition = [
            [
                'subject' => ['type' => 'FormData', 'value' => 'form_input_1'],
                'operator' => '=',
                'value' => ['type' => 'String', 'value' => 'abc'],
            ],
        ];
        $query = ProcessRequest::query();
        $filter = new \ProcessMaker\Filter();
        $filter->filter($query, $filterDefinition);

        $this->assertEquals(
            "select * from `process_requests` where json_unquote(json_extract(`data`, '\$.\"form_input_1\"')) = 'abc'",
            $query->toRawSql()
        );
    }
}
